// board
let board;
let boardWidth = 600;
let boardHeight = 940;
let context;

// bird
let birdWidth = 44;
let birdHeight = 34;
let birdX = boardWidth / 8; // punto dove si posiziona bird
let birdY = boardHeight / 2; // ci porta al centro 

// oggetto java bird
let bird = {
    x: birdX,
    y: birdY,
    width: birdWidth,
    height: birdHeight
}

// pipes
let pipeArray = [];
let pipeWidth = 84;
let pipeHeight = 600;
let pipeX = boardWidth;
let pipeY = 0;

let topPipeImg;
let bottomPipeImg;
let coinImg;

// physics
let velocityX = -3; // velocita pipe 
let velocityY = 0; // velcoita bird salto
let gravity = 0.4;

// game logic
let gameOver = false;
let gameStarted = false; // controlla se siamo nel menu iniziale
let pipeInterval; // gestisce il timer delle pipe

// score
let score = 0;

// music
let wingSound = new Audio("./sfx_wing.wav");
let hitSound = new Audio("./sfx_hit.wav");
let bgm = new Audio("./bgm.mp3");
bgm.loop = true;

// Hertz monitor (suggerito da gemini)
const TARGET_FPS = 60;
const FPS_DELTA = 1000 / TARGET_FPS; // Circa 16.6ms per frame
let deltaAccum = 0;
let lastTime = 0; // Serve per calcolare quanto tempo è passato

let scoreSaved = false; 

window.onload = function() {
    board = document.getElementById("board");
    board.height = boardHeight;
    board.width = boardWidth;
    context = board.getContext("2d"); // usato per disegnare sulla board

    // carico immagine
    birdImg = new Image();
    birdImg.src = "./flappybird.png";
    birdImg.onload = function() {
        context.drawImage(birdImg, bird.x, bird.y, bird.width, bird.height);
    }

    topPipeImg = new Image();
    topPipeImg.src = "./toppipe.png";

    bottomPipeImg = new Image();
    bottomPipeImg.src = "./bottompipe.png"

    coinImg = new Image();
    coinImg.src = "./Coin.png";

    requestAnimationFrame(update); // serve per chiamare update
    document.addEventListener("keydown", moveBird); // ogni volta che premi la key si muove il bird

    // per utenti mobile
    document.addEventListener("touchstart", moveBird, { passive: false });

}

function update(time) {
    requestAnimationFrame(update);
    
    if (!lastTime) { lastTime = time; } // Gestione primo frame
    let delta = time - lastTime;
    lastTime = time;
    deltaAccum += delta;

    // Usiamo 'if' invece di 'while' per evitare di disegnare più volte nello stesso frame(60FPS)
    if (deltaAccum >= FPS_DELTA) {
        
        // Resettiamo l'accumulatore togliendo il tempo speso (16.6ms)
        deltaAccum -= FPS_DELTA;

        // blocca le pipe e tutto il resto (nel frame successivo, viene prima eseguita la if sotto)
        if (gameOver) 
           return;

        context.clearRect(0, 0, board.width, board.height); // cancelliamo stato precedente

        // LOGICA START SCREEN
        if (!gameStarted) {
            // disegna solo il bird fermo
            context.drawImage(birdImg, bird.x, bird.y, bird.width, bird.height);
            
            // disegna il testo iniziale
            context.fillStyle = "white";
            context.font = "38px sans-serif";
            context.fillText("Premi SPAZIO per iniziare", 83, 300);
            return; 
        }

        // bird
        velocityY += gravity;
        bird.y = Math.max(bird.y + velocityY, 0); // applica gravita alla bird.y corrente, e limitiamo il top del canvas
        context.drawImage(birdImg, bird.x, bird.y, bird.width, bird.height); // almeno abbiamo sempre lo sprite al secondo

        if (bird.y > boardHeight) {
            gameOver = true;
        }

        // pipes
        for (let i = 0; i < pipeArray.length; ++i) {
            let cur = pipeArray[i];
            cur.x += velocityX;
            context.drawImage(cur.img, cur.x, cur.y, cur.width, cur.height);

            if (!cur.passed && bird.x > cur.x + cur.width) {
                score += 0.5; // 0.5 in quanto abbiamo due pipe
                cur.passed = true;
            }

            if (detectCollision(bird, cur)) {
                hitSound.play();
                gameOver = true;
            }
        }

        // cancelliamo pipe
        while (pipeArray.length > 0 && pipeArray[0].x < -pipeWidth) { // -pipeWidth per non farlo vedere nello schermo
            pipeArray.shift(); // cancella il primo elemento array
        }

        // score
        context.fillStyle = "white";
        context.font = "45px sans-serif";
        context.textAlign = "center"; // Centra il testo rispetto alla coordinata X
        context.fillText(score, boardWidth / 2, 45);

        // monete
        let currentCoins = Math.floor(score / 5);
        let iconSize = 35;
        let spacing = 10; // Spazio tra icona e testo
        let totalWidth = iconSize + spacing + context.measureText(currentCoins).width;

        // Calcoliamo il punto di partenza X affinché il gruppo sia centrato
        let startX = (boardWidth - totalWidth) / 2;
        let iconY = 60; // Abbassato leggermente per non sovrapporsi allo score

        // Disegna l'icona
        context.drawImage(coinImg, startX, iconY, iconSize, iconSize);

        // Disegna il numero
        context.fillStyle = "#FFD700";
        context.font = "35px sans-serif";
        context.textAlign = "left"; // Reset per allinearlo correttamente all'icona
        context.fillText(currentCoins, startX + iconSize + spacing, iconY + 30);

        if (gameOver) {
            context.fillStyle = "white"; // resettiamo
            context.fillText("GAME OVER", 200, 320); // 320 in quanto sarebbe la meta della board(asse y)
            context.fillText("Premi Spazio Per Rigiocare", 90, 400);
            bgm.pause();
            bgm.currentTime = 0; // resetta completamente
            
            // fermiamo la generazione dei tubi quando si perde
            clearInterval(pipeInterval);

            if (!scoreSaved) {
                saveScore(score);
                scoreSaved = true; // Segniamo che abbiamo salvato
            }
        }
    }
}

function placePipes() {
    if (gameOver) {
        return;
    }
    // math random restituisce 0 o 1
    let randomPipeY = pipeY - pipeHeight / 4 - Math.random() * (pipeHeight / 2);
    let openingSpace = board.height / 4; // spazio in cui passa il bird

    let topPipe = {
        img: topPipeImg,
        x: pipeX,
        y: randomPipeY,
        width: pipeWidth,
        height: pipeHeight,
        passed: false
    }

    pipeArray.push(topPipe);

    let bottomPipe = {
        img: bottomPipeImg,
        x: pipeX,
        y: randomPipeY + pipeHeight + openingSpace,
        width: pipeWidth,
        height: pipeHeight,
        passed: false
    }

    pipeArray.push(bottomPipe);
}

function moveBird(e) {
    if (e.type === "touchstart" || e.code == "Space" || e.code == "ArrowUp" || e.code == "KeyW") {
        
        // Impedisce lo scrolling o lo zoom accidentale su mobile
        if (e.type === "touchstart") {
            e.preventDefault();
        }
        
        // LOGICA AVVIO GIOCO
        if (!gameStarted) {
            gameStarted = true;
            // facciamo partire i tubi
            pipeInterval = setInterval(placePipes, 1500); 
        }

        if (bgm.paused) {
            bgm.play();
        }
        wingSound.play();
        // jump
        velocityY = -6; // -6 per farlo avvicinare all'asse (0,0) quindi in alto
    }

    // reset game
    if (gameOver) {
        bird.y = birdY;
        pipeArray = [];
        score = 0;
        gameOver = false;
        gameStarted = true; // Quando resetti, il gioco è considerato avviato

        scoreSaved = false;
        
        // riavvia i tubi
        pipeInterval = setInterval(placePipes, 1500);
        
        // reset gravità per non farlo cadere subito velocissimo
        velocityY = 0; 
    }
}

function detectCollision(a, b) {
    return a.x < b.x + b.width &&
        a.x + a.width > b.x &&
        a.y < b.y + b.height &&
        a.y + a.height > b.y;
}

// Funzione per inviare i dati a PHP
function saveScore(finalScore) {
    // Usiamo fetch per inviare i dati senza ricaricare la pagina
    fetch('../save_score.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'score=' + finalScore
    })
    .then(response => response.json())
    .then(data => {
        console.log(data.message); // Leggi il messaggio dal JSON
        if(data.new_record) {
            alert("Complimenti! " + data.message); // Esempio
        }
    })
    .catch(error => console.error('Errore:', error));
}