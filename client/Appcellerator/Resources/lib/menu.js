// Credit: to Justin Toth
// ATTRIB: http://developer.appcelerator.com/question/87341/options-menu-that-works-on-android-and-iphone-on-ti-14-and-15
// ATTRIB: http://developer.appcelerator.com/question/137359/more-than-2-buttons-on-titlebar

function AddMenu (win, buttons) {
    if (Ti.Platform.name == 'android') { isAndroid = true; } else { isAndroid = false; } ;
    data = [];

    if (!isAndroid) {
        //create iphone 
        for (var k = 0; k < buttons.length; k++) {
            data[k] = Ti.UI.createButton({ title: buttons[k].title, style: Ti.UI.iPhone.SystemButtonStyle.BORDERED });
            data[k].addEventListener("click", buttons[k].clickevent);
        }
		var toolbar = Ti.UI.iOS.createToolbar ({
			top: 0,
			items: data
			
		});
		
        win.add(toolbar);
    }
    else {
        var activity = win.activity;
        activity.onCreateOptionsMenu = function (e) {
            var optionsmenu = e.menu;
            for (var k = 0; k < buttons.length; k++) {
                data[k] = optionsmenu.add({ title: buttons[k].title });
                data[k].addEventListener("click", buttons[k].clickevent);
            }
        };
	}
};