// Main Window
	
	var MainWin = Ti.UI.createWindow({
		exitOnClose: true,
		fullscreen : true,
  		backgroundColor:'white'
	});
	
	MainWin.orientationModes=[
    	Ti.UI.PORTRAIT
	];
	
	var view = Titanium.UI.createView({
	    height:100,
	    width:'100%',
	    backgroundColor:'#FFFFFF',
	    top: height-(height*.4),
	});

	var qrScanButton = Titanium.UI.createButton({
   		title: 'CONTINUOUS SCAN',
   		top: 0, 
   		width: '50%',
   		left: 0,
   		height: 100
	});

	qrScanButton.addEventListener("click", function(e) {
		ScanCode(true);
	});

	view.add(qrScanButton);

	var qrScanButtonSingle = Titanium.UI.createButton({
   		title: 'NOTE SCAN',
   		top: 0, 
   		width: '50%',
   		right: 0,
   		height: 100
	});

	qrScanButtonSingle.addEventListener("click", function(e) {
		ScanCode(false);
	});

	view.add(qrScanButtonSingle);

 	MainWin.add(view);


	var confimg = Titanium.UI.createImageView({
		image: Ti.Filesystem.getFile(Titanium.Filesystem.resourcesDirectory + "gfx/mainfram3-logo.png"),
	    width: height-(height*.6),
	    height: (height-(height*.6))/2,
	    top: 10,
	    left: width/2 -((height-(height*.6))/2)
	});

	MainWin.add(confimg);

	var vendimg = Titanium.UI.createImageView({
		image: Ti.Filesystem.getFile(Titanium.Filesystem.resourcesDirectory + "gfx/oat-logo.png"),
	    width: height-(height*.6),
	    height: (height-(height*.6))/2,
	    top: ((height-(height*.6))/2)+40,
	    left: width/2 -((height-(height*.6))/2)
	});

	MainWin.add(vendimg);
	
	var lblVendor = Ti.UI.createLabel({
		text: 'Please scan an Authorization Tag now.',
		top: ((height-(height*.6))/2)+40+((height-(height*.6))/2)+40,
	    textAlign: Ti.UI.TEXT_ALIGNMENT_CENTER,
		width: Ti.UI.SIZE
	});

	MainWin.add(lblVendor);

	var scrollView = Ti.UI.createScrollView({
		top: height-(height*.4)+100,
  		bottom:0,
  		contentHeight: 'auto',
  		layout: 'vertical',
  		scrollType: 'vertical'
	});

	MainWin.add(scrollView);
 	// Add the menu to refresh user DB
 	
	AddMenu(MainWin, [{
		title: "Refresh Attendees",
		clickevent: function () { OAT_SyncUsers(); }
	},
	{
		title: "Push Attendees",
		clickevent: function () { OAT_Send_Scans(); }
	},{
		title: "Logout",
		clickevent: function () { OAT_Logout(); }
	},{
		title: "About",
		clickevent: function () { OAT_About(); }
	}]);

// Activity Indicator

	var style;
	if (Ti.Platform.name === 'iPhone OS'){
	  style = Ti.UI.iPhone.ActivityIndicatorStyle.BIG_DARK;
	}
	else {
	  style = Ti.UI.ActivityIndicatorStyle.BIG_DARK;
	}

	var activityView = Titanium.UI.createView({
	    height:'100%',
	    width:'100%',
	    backgroundColor:'#000000',
        opacity:.6,
        visible: false,
        zIndex: 99
	});

	var activityIndicator = Titanium.UI.createActivityIndicator
    ({
        color: 'White',
        width:'auto',
		height:Ti.UI.SIZE,
  		width:Ti.UI.SIZE,
        style: style,
    });
    
    activityView.add(activityIndicator);
    MainWin.add(activityView);

MainWin.open();
	
OAT_Add_Log("Application Started!");

function OAT_About() {
	var OAT_AboutWindow = Ti.UI.createWindow({
		backgroundColor:'transparent', 
		opacity:1,
		height:(height/2),
		width: width * .8,
	});

	var tview = Ti.UI.createView({
		opacity:1,
		backgroundColor:'black',
		top: 10
	});
	
	var textArea = Ti.UI.createTextArea({
		opacity:1,
	    left: 0,
	    right: 0,
	    top: 10,
	    bottom: 60,
	    editable:false,
	    value: String.format(L('about'), config.version)
//		value: L('about')
	    // height: height*.375
	});
	
	var OKButton = Titanium.UI.createButton({
   		title: 'OK',
   		bottom: 10, 
   		width: 100,
   		height: 50,
   		left: (OAT_AboutWindow.width/2) - 50
	});

	OKButton.addEventListener('click', function(e) {
		OAT_AboutWindow.close();
	});
 
 	tview.add(textArea);
 	tview.add(OKButton);

 	OAT_AboutWindow.add(tview);
 	OAT_AboutWindow.open();
}


	