const usersPicture = {}; // something like a cache for the users pfp
const chats = {}; // a json of all the chats and their messages. "conversation_hash": { "message_hash_1": {"sender": "sender_hash"} } 
let unsentMessages = []; // so, this json will keep the messages that have not been delivered yet. Once the server confirms that they have been delivered then it will remove them from here

let aesKey;
let con; // the websocket connection
let currentChat = "";
let owner; // owner means our user
let jwt;
let isBitchOpen = true;
let mfProfileImageListener; // gambiarra

let isMobileViewport = false;
let isConversationOpen = false;
let selectablesState = false;

const maxUsernameLength = 10;
const maxLastSaidLength = 15;

const dataLoadedEvent = new Event("dataLoaded");
