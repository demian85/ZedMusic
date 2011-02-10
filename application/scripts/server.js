/**
 * Websocket support
 * @requires socket.io
 */
var http = require('http'),
	sys = require('sys'),
    io = require('socket.io');

var server = http.createServer(function(req, res) {
	res.writeHead(200, {'Content-Type': 'text/plain'});
	res.end('');
});

// socket.io
var socket = io.listen(server);
socket.on('connection', function(client) {
	client.on('message', function(msg) {
		var data = JSON.parse(msg);
		if (data.action == 'update') {
			client.broadcast(JSON.stringify({action : 'update'}));
		}
	});
	client.on('disconnect', function() { });
});

server.listen(8080);