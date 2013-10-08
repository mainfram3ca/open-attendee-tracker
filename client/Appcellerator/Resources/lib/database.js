// Initialize the DB
function db_init () {
	var db = Titanium.Database.open('datafile');
	db.execute('CREATE TABLE IF NOT EXISTS users (key TEXT UNIQUE, fname TEXT, lname TEXT)');
	db.execute('CREATE TABLE IF NOT EXISTS scans (scanid INTEGER PRIMARY KEY AUTOINCREMENT, key TEXT, time TEXT, notes TEXT, synced INT)');
	db.close();
	Ti.API.info('[DB] INITIALIZED');
};

function db_remove() {
	var db = Titanium.Database.open('datafile');
	db.close();
	db.remove();
	Ti.API.info('[DB] REMOVED');
}

// Get the current time
function current_time() {
	var value = Math.floor(new Date().getTime() / 1000);
	Ti.API.debug("[DB] current_timestamp=" + value);
	return value;
};

// Get a user from the Database
// Input: 	Users Key - String
// Output: 	Array()
//			fname - Human First Name
// 			lname - Human Last Name or Null depending on $Config

function db_getUser(key) {
	var db = Titanium.Database.open('datafile');
	var result = new Array();
	var rs = db.execute('SELECT fname,lname FROM users WHERE key = ?', key);
	if (rs.isValidRow()) {
		Ti.API.info('[DB] Got User: key[' + key + ']: ' + rs.fieldByName('fname'));
		result['fname'] = rs.fieldByName('fname');
		result['lname'] = rs.fieldByName('lname');
	} else {
		Ti.API.info('[DB] MISS User: key[' + key + ']');
		result['fname'] = "Unknown"; 			
		result['lname'] = null;
	}
	rs.close();
	db.close();
	return result;
};

// Get a set of scans from the Database
// Input: 	All or UnSynced - Boolian
// Output: 	Array() of Objects
//			scanid - The ID of the scan in the database
//			key - The human's key
//			time - Time of the scan - UnixTime
//			note - Any notes added for the scan

function db_getScans(allscans) {
	var db = Titanium.Database.open('datafile');
	var scandata = new Array();
	var result = null;
	if (allscans == true) {
		var rs = db.execute('SELECT scanid, key, time, notes, synced FROM scans');
	} else {
		var rs = db.execute('SELECT scanid, key, time, notes FROM scans WHERE synced = 0');
	}
	while (rs.isValidRow()) {
		Ti.API.info('[DB] Got Scan: key[' + rs.fieldByName('key') + ']');
		scandata.push({scanid: rs.fieldByName('scanid'), key: rs.fieldByName('key'), time: rs.fieldByName('time'), notes: JSON.parse(rs.fieldByName('notes'))});
		rs.next();
	}
	rs.close();
	db.close();
	return scandata;
};

// Get a single scan from the database
function db_getScan(item) {
	var db = Titanium.Database.open('datafile');
	var result = new Array();
	var rs = db.execute('SELECT scanid, key, time, notes FROM scans WHERE scanid = ? ', item);
	if (rs.isValidRow()) {
		result['key'] = rs.fieldByName('key');
		result['id'] = rs.fieldByName('scanid');
		result['notes'] = JSON.parse(rs.fieldByName('notes'));
	}
	rs.close();
	db.close();
	return result;
}

// Put a user into the database
// Input: 	Key - The human's key
//			fname - The human's First name
//			lname - The human's last name
// Output: 	Success/Failure - Boolian 		TODO

function db_putUser(db, key, fname, lname) {
	// var db = Titanium.Database.open('datafile');
	if (config.debug == true) Ti.API.info('[DB] PUT User: ' + key);
	if (lname != null) {
		var query = 'INSERT OR REPLACE INTO users (key, fname, lname) VALUES (?, ?, ?);';
		db.execute(query, key, fname, lname);
	} else {
		var query = 'INSERT OR REPLACE INTO users (key, fname) VALUES (?, ?);';
		db.execute(query, key, fname);
	}
	// db.close();
};

// Put a badge scan into the database
// Input:	key - The human's key
//			notes - Any notes from the scan
//			synced - if the scan has been synced
// Output: 	Success/Failure - Boolian 		TODO

function db_putScan(key, notes, synced) {
	var db = Titanium.Database.open('datafile');
	Ti.API.info('[DB] PUT Scan: ' + key);
	var query = 'INSERT INTO scans (key, time, notes, synced) VALUES (?, ?, ?, ?);';
	db.execute(query, key, current_time(), JSON.stringify(notes), synced);
	var rowid = db.getLastInsertRowId();
	db.close();
	return rowid;
};

// Update a scanned item
function db_updateScan(id, note) {
	var db = Titanium.Database.open('datafile');
	Ti.API.info('[DB] Update Sync: ' + id);
	var query = 'UPDATE scans SET synced=?, notes=? WHERE scanid=?;';
	db.execute(query, 0, JSON.stringify(note), id);
	db.close();
	return true;
}
// Mark a scan entry as synced
// Input: 	id - The scan id
// Output: 	Success/Failure - Boolian 		TODO

function db_SyncedUser(id) {
	var db = Titanium.Database.open('datafile');
	Ti.API.info('[DB] Update Sync: ' + id);
	var query = 'UPDATE scans SET synced=? WHERE scanid=?;';
	db.execute(query, 1, id);
	db.close();
	return true;
}
