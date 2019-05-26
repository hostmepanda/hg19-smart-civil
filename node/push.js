

const http = require('http');
const path=require("path");
const express = require('express');
const socketio = require("socket.io");
const app = express();
const server = http.createServer(app);
const io = socketio(server);

const port = 8080;
const publicFolder=path.join(__dirname,"/publicFolder");
app.use(express.static(publicFolder));

io.on("connection",()=>{
    console.log("client is connected");
    setInterval(()=>{
        console.log(Math.round((new Date())/1000)+" Event is emitted");
        
        io.emit("push", { "TS":"1559907920","text":"7 июня отключение горячей воды"});
    },30000);
});

server.listen(port,()=>{
    console.log("Server is up on 8080 port");
    
});

// // Dependencies
// const fs = require('fs');
// const http = require('http');
// const https = require('https');
// const express = require('express');
// const socketio = require("socket.io");
// const path=require("path");

// const publicFolder=path.join(__dirname,"/publicFolder");

// // Certificate
// const privateKey = fs.readFileSync('/etc/letsencrypt/live/hg.hatarisu.ru/privkey.pem', 'utf8');
// const certificate = fs.readFileSync('/etc/letsencrypt/live/hg.hatarisu.ru/cert.pem', 'utf8');
// const ca = fs.readFileSync('/etc/letsencrypt/live/hg.hatarisu.ru/chain.pem', 'utf8');

// const credentials = {
//     key: privateKey,
//     cert: certificate,
//     ca: ca
// };
// const app = express();



// // Starting both http & https servers
// const httpServer = http.createServer(app);
// const httpsServer = https.createServer(credentials, app);
// const io = socketio(httpServer);
// // httpServer.listen(8080, () => {
// //     console.log('HTTP Server running on port 80');
// // });

// app.use(express.static(publicFolder));
// app.use((req, res) => {
//     res.send('Hello there !');
// });

// io.on("connection",()=>{
//     console.log("Socket io client is connected");
    
// });


// httpServer.listen(8080, () => {
//     console.log('HTTPS Server running on port 8080');
//     console.log(`Serving static from ${publicFolder}`);
// });

