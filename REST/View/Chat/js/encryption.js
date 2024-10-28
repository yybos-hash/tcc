async function hmacSHA256 (key, data) {
    const encoder = new TextEncoder();
    const encodedKey = encoder.encode(key);
    const encodedData = encoder.encode(data);

    const keyBuffer = await window.crypto.subtle.importKey(
        'raw',
        encodedKey,
        { name: 'HMAC', hash: { name: 'SHA-256' } },
        false,
        ['sign']
    );

    const signature = await window.crypto.subtle.sign(
        'HMAC',
        keyBuffer,
        encodedData
    );

    return Array.from(new Uint8Array(signature)).map(byte => byte.toString(16).padStart(2, '0')).join('');
}

function generateSalt (length) {
    const randomBytes = new Uint8Array(length);
    window.crypto.getRandomValues(randomBytes);
    return randomBytes;
}

// ---------------------------------------------------


// Function to convert a hex string to a Uint8Array
function hexStringToUint8Array (hexString) {
    return new Uint8Array(hexString.match(/.{1,2}/g).map(byte => parseInt(byte, 16)));
}

// Function to convert a Uint8Array to a hex string
function uint8ArrayToHexString (uint8Array) {
    return Array.prototype.map.call(uint8Array, x => ('00' + x.toString(16)).slice(-2)).join('');
}

// Function to encrypt a message using AES-GCM
async function encryptMessage (message, key) {
    key = hexStringToUint8Array(key);

    // Import AES key from ArrayBuffer
    const aesKey = await window.crypto.subtle.importKey(
        "raw",
        key.buffer,
        { name: "AES-GCM" },
        true,
        ["encrypt", "decrypt"]
    );

    const iv = crypto.getRandomValues(new Uint8Array(12)); // Generate a random initialization vector

    const encodedMessage = new TextEncoder().encode(message);

    const cipher = await window.crypto.subtle.encrypt(
        {
            name: 'AES-GCM',
            iv: iv,
        },
        aesKey,
        encodedMessage
    );

    const authTag = cipher.slice(-16); // Get the authentication tag

    return {
        iv: uint8ArrayToHexString(iv),
        encrypted: uint8ArrayToHexString(new Uint8Array(cipher.slice(0, -16))),
        authTag: uint8ArrayToHexString(new Uint8Array(authTag))
    };
}

// Function to decrypt a message using AES-GCM
async function decryptMessage (encryptedData, key, iv, authTag) {
    const ivUint8Array = hexStringToUint8Array(iv);
    const authTagUint8Array = hexStringToUint8Array(authTag);
    const encryptedDataUint8Array = hexStringToUint8Array(encryptedData);

    const concatened = new Uint8Array(encryptedDataUint8Array.length + authTagUint8Array.length);
    concatened.set(encryptedDataUint8Array);
    concatened.set(authTagUint8Array, encryptedDataUint8Array.length);

    // Import AES key from ArrayBuffer
    const aesKey = await window.crypto.subtle.importKey(
        "raw",
        hexStringToUint8Array(key).buffer,
        { name: "AES-GCM" },
        true,
        ["encrypt", "decrypt"]
    );

    const decrypted = await window.crypto.subtle.decrypt(
        {
            name: 'AES-GCM',
            iv: ivUint8Array,
            additionalData: new Uint8Array(0), // Empty array since no additional data is used
            tagLength: 128,
        },
        aesKey,
        concatened
    );

    return new TextDecoder().decode(decrypted);
}

// secret key Diffie-Hellman
function bcpowmod (base, exponent, modulus) {
    let result = 1n;
    base = base % modulus;

    while (exponent > 0n) {
        if (exponent % 2n === 1n) {
            result = (result * base) % modulus; // Perform modular reduction
        }

        exponent >>= 1n; // Use bitwise right shift for BigInt
        base = (base * base) % modulus; // Perform modular reduction
    }

    return result.toString(); // Convert result to string before returning
}

// chat gpt made this, idk if its gonna work (Diffie-Hellman)
function randomPrivateKey (bitLength) {
    const byteArray = new Uint8Array(Math.ceil(bitLength / 8));
    crypto.getRandomValues(byteArray);
    const hexString = Array.from(byteArray).map(byte => byte.toString(16).padStart(2, '0')).join('');
    const decimalValue = BigInt(`0x${hexString}`);
    return decimalValue;
}
