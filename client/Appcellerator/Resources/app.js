/*
 * Single Window Application Template:
 * A basic starting point for your application.  Mostly a blank canvas.
 * 
 * In app.js, we generally take care of a few things:
 * - Bootstrap the application with any data we need
 * - Check for dependencies like device type, platform version or network connection
 * - Require and open our top-level UI component
 *  
 */

//bootstrap and check dependencies
if (Ti.version < 1.8 ) {
	alert('Sorry - this application template requires Titanium Mobile SDK 1.8 or later');	  	
}

//render appropriate components based on the platform and form factor
var osname = Ti.Platform.osname,
	version = Ti.Platform.version,
	height = Ti.Platform.displayCaps.platformHeight,
	width = Ti.Platform.displayCaps.platformWidth;

// Lets make sure our height/width is oriented correctly.
if (height < width) {
	theight = width;
	width = height;
	height = theight;
	theight = null;
}

//considering tablet to have one dimension over 900px - this is imperfect, so you should feel free to decide
//yourself what you consider a tablet form factor for android
var isTablet = osname === 'ipad' || (osname === 'android' && (width > 899 || height > 899));
var isAndroid = (Ti.Platform.osname=='android') ? true : false;
var isIphone = (Ti.Platform.osname=='iphone') ? true : false;

var Barcode = require('ti.barcode');

Ti.include('functions.js');
Ti.include('lib/cachedimg.js');
Ti.include('lib/menu.js');
Ti.include('lib/database.js');
Ti.include('lib/toastnotification.js');
Ti.include('windows.js');
Ti.include('barcode.js');

config = new Array(); 
config.loggedin = false;
config.loggingout = false;
config.version = Titanium.App.version;
config.debug = false;

AboutTxt = "This is the about text.\nThis will be added later.\n";

var OAT_SU_Loader = Titanium.Network.createHTTPClient();
var OATL_Loader = Titanium.Network.createHTTPClient();
var OAT_SendUsers = Titanium.Network.createHTTPClient();

// Some basic barcode reader stuff
Barcode.allowRotation = false;
Barcode.displayedMessage = ' ';
Barcode.allowMenu = false;
Barcode.allowInstructions = false;
Barcode.useLED = false;

// Init the keychain and try see if we have a key
var keychain = require('com.obscure.keychain');
var scancodeItem = keychain.createKeychainItem('localkey');
if (scancodeItem.valueData != undefined) {
	// We've been logged in before, lets log in again
	OAT_Login(scancodeItem.valueData);
};


// Initialize the DB
db_init();


// Interval
// -- Attempt to sync every 5 minutes
setInterval(function(){ OAT_Send_Scans(); },5*60*1000); // 5 Mins
