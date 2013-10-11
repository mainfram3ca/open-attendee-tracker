// Uses an intent to use a scanner - keeping this here for history
function ScanCode_Intent() {
	  var data;
	  var intent = Ti.Android.createIntent({
	    action: "com.google.zxing.client.android.SCAN"
	  });
	  intent.putExtra("SCAN_MODE", "QR_SCAN_MODE");
	  var activity = Ti.Android.currentActivity;
	  return activity.startActivityForResult(intent, function(e) {
	    if (e.resultCode == Ti.Android.RESULT_OK) {
			var contents = e.intent.getStringExtra("SCAN_RESULT");
			var format = e.intent.getStringExtra("SCAN_RESULT_FORMAT");
	     	data = {result: contents, format: format};
			ScanCode_CallBack(data);
	    } else if (e.resultCode == Ti.Android.RESULT_CANCELED) {
			showMessageTimeout("Scan canceled!");
	    }
	  });
}

// Initialize the scan system, either the internal one, or the intent
// Input: 	keepopen - boolean - For the internal scanner, does it stay open
// Output:	NONE

function ScanCode(keepopen) {
	// Use one of the defined scan types
	// For now, use the intent since we don't have another option yet
	//ScanCode_Intent();
	// Test the login without the need to accutally scan a code
	// data = {result: contents, format: "QR_CODE"};
	config.keepon = keepopen;
	if (config.loggedin == false) {
		ScanBarCode(false);
	} else if (keepopen == false) {
		ScanBarCode(false);
	} else {
		ScanBarCode(true);
	}
}

// Callback from the scanner
// Input: 	data - Object - array of data
// 				format - the format of the code (Should be QR_CODE)
//				result - the data returned
// Output:	None

// TODO: Try other non-url QR codes

function ScanCode_CallBack(data) {
	if (data != null && data.format == "QR_CODE") {
		// Start working on what todo
		OAT_Scanned_Main(data.result);
	} else if (data.format != "QR_CODE") {
		showMessageTimeout("Invalid Code!");
       	OAT_Add_Log("Invalid code scanned");
	} else {
		showMessageTimeout('Scan Canceled!');
	}
//	ScanCode();
}

function OAT_Scanned_Main(scan_code) {
		// alert ("Contents: " + scan_code);
		// Process the scan code
		try {
			var scandata = OAT_Split_Code(scan_code);
		} catch(e) {
			alert (e.message);
			return false;
		} 
			
		if (config.loggedin == false && scandata['human'] == false ) { 
			// If we're not logged in, go to the URL but add a android app identifer key so the back end knows how to talk to us
			OAT_Login(scan_code);
			// The server should respond with a bunch of JSON style config values that we store and use for subsiquent requests
		} else if (config.loggedin == false && scandata['human'] == true ) {
			alert("We're not logged in yet, please scan your vendor badge");
		} else if (config.loggedin == true && scandata['human'] == false) {
			// Looks like we're already logged in, we should see if they want to wipe the DB and logout.
			alert("We're logged in already!");
		} else if (config.loggedin == true && scandata['human'] == true) {
			// We're logged in, and it's a human badge
			OAT_Badge_Scan(scandata);
		} else {
			// Something bad happened. 
			alert("Something is wrong with the badge.");
		}
}

function OAT_Split_Code(scan_code) {
	var OATSC_response = {};
	OATSC_response['human'] = false;
	// Break up the scan code into its parts
	// First things first, remove the BaseURL
	// If we're not logged in, just look for the badge type without baseurl'
	if (config.loggedin == false) {
			if (scan_code.indexOf("\/human.php") > 1) {
				OATSC_response['human'] = true;
			}
	} else {
		// Do some basic checks, like see if there is a ? and a & in the code
		if (scan_code.indexOf("human.php") == -1 | scan_code.indexOf("?") == -1 | scan_code.indexOf(config.config.baseurl) != 0) {
			var lerror = new Error('Invalid Code');
			throw lerror;
		}
		scan_code = scan_code.replace(config.config.baseurl, "");
		// Split up the remainder by ? to get the code type
		var splita = scan_code.split("?");
		if (splita[0] == "human.php") {
			OATSC_response['human'] = true;
		}
		var splitb = splita[1].split("&");
		for (var i = 0; i < splitb.length; ++i) {
		    var splitc = splitb[i].split("=");
		    OATSC_response[splitc[0]] = splitc[1];
		}
	}
	return OATSC_response;
}

function OAT_Login(scan_code) {
	OAT_Working(true);
	var url = scan_code + "&json";
	Ti.API.info("URL: " + url);
	OATL_Loader.open('GET', url);
	OATL_Loader.onerror = function () {
		alert("Login failed, please contact an admin");
		OAT_Working(false);
	};
	OATL_Loader.onload = function () {
		var response = this.responseText;
		var Data = JSON.parse(response);
		Ti.API.info("Response: " + response);
		if (Data.response == 200) {
			config.loggedin = true;
			config.config = Data;
			OAT_downloadimg(Data);
			showMessageTimeout("Logged In!");
	       	OAT_Add_Log("Vendor logged in");
			OAT_Working(false);
	       	OAT_SyncUsers();
		} else {
			// there was an error with the login
			alert("Login failed, please contact an admin");
			OAT_Working(false);
		}
	};
	OATL_Loader.send();
}

function OAT_downloadimg(Data) {
	if (Data.confimg != null) {
		cachedImageView("images", Data.confimg, confimg);
	}
	cachedImageView("images", Data.baseurl + "/gfx/" + Data.key, vendimg);
	lblVendor.text = Data.vendorname;
}


// Next set of functions handle when a manual sync user request happens

function OAT_SyncUsers() {
	// check if we're logged in
	if (config.loggedin == false) {
		alert ("You're not logged in yet.");
		return;
	}
	// A provision config value should be set to use or not use pins.
	usepin = false;
	if (usepin == false) {
		OAT_SyncUsers_Request(false);
	} else {
		// We're going to ask for a pin, just to stop people from figuring out they can download all the users, then figure out how to RE the scans. 
		var textField = Ti.UI.createTextField({
		    left: 0,
		    right: 0,
		    height: 40,
		    hintText: 'Enter admin pin',
		    keyboardType: Ti.UI.KEYBOARD_NUMBER_PAD
		});
	 
		var dialog  = Ti.UI.createOptionDialog({
		    androidView:textField,
		    buttonNames:['Cancel','OK']
		});
	 
		dialog.addEventListener('click', function(e) {
		    if (e.index == 1) { // we read it only if get it is pressed
				// Great, now, Submit the request to the backend with the pin
				OAT_SyncUsers_Request(textField.value); 
		    } else {
		    	// Show a cancled message
		    	showMessageTimeout("Canceld!");
		    }
	 	});
	 	
	 	dialog.show();
	}
}

function OAT_SyncUsers_Request(pin) {
	// Make this a new function because of the Listener code flow, makes it look cleaner. 	
	OAT_Working(true);
    if (pin == false) {
    	url = config.config.baseurl + "userlist.php?json";
    } else {
    	url = config.config.baseurl + "userlist.php?json&pin=" + pin;
    }
	OAT_SU_Loader.open('GET', url);
	OATL_Loader.onerror = function () {
		OAT_Working(false);
	};
	OAT_SU_Loader.onload = function () {
		var Data = JSON.parse(this.responseText);
		Ti.API.info("JSON: " + JSON.stringify(Data));
		if (Data.response == 200) {
			OAT_SyncUsers_Response(Data);
		} else if (Data.response == 302) {
			config.loggedin = false;
			alert ("You were logged out - you need to relogin");
		}
		OAT_Working(false);
	 };
	 OAT_SU_Loader.send();
}

function OAT_SyncUsers_Response(data) {
	// Handle the data returned and input into the database
	// Ti.API.info(JSON.stringify(data));
	var db = Titanium.Database.open('datafile');	
	db.execute('BEGIN;');	
	for (var i = 0; i < data.user.length; ++i) {
		if (config.debug == true) Ti.API.info("Key: " + data.user[i].key + "  fname: " + data.user[i].fname + " lname: " + data.user[i].lname);
		// TODO: Don't open and close the DB for EVERY F'ing USER. Need to clean this up. 
		db_putUser(db, data.user[i].key, data.user[i].fname, data.user[i].lname);
	} 
	db.execute('COMMIT;');
	db.close;
	OAT_Add_Log("Attendee users synced");
}

function OAT_Badge_Scan(data) {
	// Badge was scanned
	// Get any notes the user wants to add
	var user_name = db_getUser(data['key']);
	// Ask for notes
	if (config.keepon == true) {
		OAT_Badge_Scan_Save(data, "");
	} else {
		OAT_Badge_Scan_Dialog(data, user_name);
	}
}

function OAT_Badge_Scan_Dialog(data, user_name, text) {
	var OAT_scanWindow = Ti.UI.createWindow({backgroundColor:'Black', opacity:.7,});

	var tview = Ti.UI.createView({
		opacity:1,
		backgroundColor:'white',
		height:(height/2),
		width: width * .8,
		top: 10
	});
	
	if (user_name['lname'] == null) { 
		var ttext1 = Ti.UI.createLabel({text: 'Name: ' + user_name['fname'],left:'15dp',id:1,top:10});
	} else {
		var ttext1 = Ti.UI.createLabel({text: 'Name: ' + user_name['fname'] + " " + user_name['lname'],left:'15dp',id:1,top:10});
	}

	var textArea = Ti.UI.createTextArea({
	    left: 0,
	    right: 0,
	    top: 40,
	    bottom: 60
	    // height: height*.375
	});
	
	// If we have text, we need to display it in the text area
	if (text != undefined) {
		textArea.value = text;
	}

	var OKButton = Titanium.UI.createButton({
   		title: 'OK',
   		bottom: 10, 
   		width: 100,
   		height: 50,
   		left: (tview.width/2) - 50
	});

	OKButton.addEventListener('click', function(e) {
		if (isAndroid) {
			Ti.UI.Android.hideSoftKeyboard();
		}
		OAT_scanWindow.close();
		OAT_Badge_Scan_Save(data, textArea.value);
	});
 
	tview.add(ttext1);
 	tview.add(textArea);
 	tview.add(OKButton);

	// Autofocus the textArea
	OAT_scanWindow.addEventListener('open', function() {
    	textArea.focus();
    	if (isAndroid) {
    		textArea.softKeyboardOnFocus = Ti.UI.Android.SOFT_KEYBOARD_SHOW_ON_FOCUS;
    	}
	});
 	
 	OAT_scanWindow.add(tview);
 	OAT_scanWindow.open();
	// Attempt to sync all the un-sent badges to the server
}

function OAT_Badge_Scan_Save(data, text) {
	Ti.API.info("User Key: " + data['key']);
	if (data['id'] != undefined) {
		db_updateScan(data['id'], text);
		var id = data['id'];
	} else {
		var id = db_putScan(data['key'], text, 0);
	}
	if(id != false) {
		showMessageTimeout("Saved Scan!");
	  	var user_name = db_getUser(data['key']);
	  	if (user_name['lname'] == null) {
	  		OAT_Add_Log("Attendee scan saved: " + user_name['fname'], id);	      		
	  	} else {
	  		OAT_Add_Log("Attendee scan saved: " + user_name['fname'] + " " + user_name['lname'], id);
		}
	} else {
		showMessageTimeout("Save Failed!");
	}

}

function OAT_Send_Scans() {
	// Load up all the unsynced scans
	if (config.loggedin != true) { return false;}
	pin = false;
	var OAT_scans = db_getScans(false);
	
	if (OAT_scans.length == 0 && config.loggingout == true) { OAT_Logout_Callback(); }
	if (OAT_scans.length == 0) { return; }
	
	var OAT_scans = JSON.stringify(OAT_scans);
	Ti.API.info(OAT_scans);

    if (pin == false) {
    	url = config.config.baseurl + "usersync.php?json";
    } else {
    	url = config.config.baseurl + "usersync.php?json&pin=" + pin;
    }

	OAT_SendUsers.open('POST', url);
	OAT_SendUsers.setRequestHeader("content-type", "application/json");
	OAT_SendUsers.onload = function () {
		var Data = JSON.parse(this.responseText);
		Ti.API.info("JSON: " + JSON.stringify(Data));
		if (Data.response == 200) {
			showMessageTimeout("Attendee scans synced.");
			OAT_SendUsers_Response(Data);
			OAT_Add_Log("Attendee scans synced");
			if (config.loggingout == true) {
				OAT_Logout_Callback();
			}
		} else if (Data.response == 302) {
			config.loggedin = false;
			alert ("You were logged out - you need to relogin");
		} else {
			showMessageTimeout('Attendee scan sync failed.');
		}
	 };
	 OAT_SendUsers.send(OAT_scans);
}

function OAT_SendUsers_Response(Data) {
	// We have a bunch of scan's accepted, we need to mark them synced
	for (var i = 0; i < Data.accepted.length; ++i) {
		Ti.API.info("Scan Sync: " + Data.accepted[i]);
		db_SyncedUser(Data.accepted[i]);
	}
}

function OAT_Add_Log(msg, clickitem) {
	
		// Create the viewport
	var row = Ti.UI.createView({
		backgroundColor: 'white',
		borderColor: '#bbb',
		borderWidth: 1,
		width:'100%', height: 30,
		top: 0, left: 0
	});

	var Titlelbl = Titanium.UI.createLabel({
    	text: msg,
    	font:{fontSize:15,fontWeight:''},
    	width:'auto',
    	textAlign:'left',
    	top:3,
    	color:'black',
    	left:4
	});
	
	if (clickitem != undefined) {
		// It's clickable, create a click function with the click id. 
		row.addEventListener('click',function(e){
			OAT_update_item(clickitem);
		});
	}

	row.add(Titlelbl);
	
	scrollView.add(row);		
	//return row;
}

function OAT_update_item(clickedItem) {
	// Get the item from database
	var data = db_getScan(clickedItem);
	// get the usernane
	var user_name = db_getUser(data['key']);
	// display the dialog box
	OAT_Badge_Scan_Dialog(data, user_name, data['notes']);
}

function OAT_Working(state) {
	if (state == true) {
		activityIndicator.show();
		activityView.visible = true;
	} else {
		activityIndicator.hide();
		activityView.visible = false;
	}
	
}

function OAT_Logout() {
	// TODO check to see we're actually logged in first. 
	if (config.loggedin == true ) {
		// We're logging out, so set the Working state, set a global variable and try and send the scans.
		OAT_Working(true);
		config.loggingout = true;
		OAT_Send_Scans();
	} else {
		alert ("You're not logged in yet!");
	}
}

function OAT_Logout_Callback() {
	// Scans have been sent, let's check if there are any items left
	scans = db_getScans(false); 
	if (scans.length > 0) {
		// There was some scans that couldn't be synced, display an error.
		OAT_Working(false);
		config.loggingout = false;
		alert("There are " + scans.length + " scan(s) that can't be synced. Please see an admin.");
	} else {
		// All the scans were sent, lets do some cleanup
		db_remove();
		var activity = Titanium.Android.currentActivity; 
		activity.finish();
	}
	
}
