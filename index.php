<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no">

<title>どうぶつ崩し２</title>
<script>

let canvas,context;

let status = "ready";
let score = 0;
let stage = 0;

//スコア
let rank_score = new Array(3);
let rank_time = new Array(3);
const STORAGESTRINGS = "block2_";



const DEF_BALLSPEEDMAX = 10;
const BALLCNTMAX = 30;
const DEF_BALLSPEED = 3;
const DEF_EFFECTCNT = 20;
const DEF_EFFECTCNT_P = 600;

//0爆発　12345ブロック
const BLOCKTEXT = ["💥","🐹","🐵","🐻","🐷","🐼"];

let def_blockWidth = 50;
let def_blockHeight = 20;
let def_ballSize = 6;

const CAPSEL_COLOR_RED = "#aa0000";
const CAPSEL_COLOR_GREEN = "#00aa00";
const CAPSEL_COLOR_BULE = "#0000aa";
const CAPSEL_COLOR_WHITE = "#aaaaaa";
const CAPSELBLOCK=[CAPSEL_COLOR_RED,CAPSEL_COLOR_GREEN,CAPSEL_COLOR_BULE,CAPSEL_COLOR_WHITE];

let touchflag=false;

const STAGEDATA =[
[
1,1,1,1,1,1,1,1,0,0,
0,0,1,1,1,1,1,1,1,1,
0,0,1,1,1,1,1,1,1,1,
1,1,1,1,1,1,1,1,0,0,
1,1,1,1,1,1,1,1,0,0,
]
,

[
0,0,1,1,1,1,1,1,0,0,
0,0,2,2,2,2,2,2,0,0,
1,2,3,4,5,5,4,3,2,1,
1,1,1,1,1,1,1,1,1,1,
0,0,0,0,0,0,0,0,0,0
]
,

[
5,5,5,5,5,5,5,5,5,5,
1,3,3,1,3,3,1,3,3,1,
2,0,0,0,0,0,0,0,0,2,
2,4,4,0,1,1,0,4,4,2,
2,2,2,0,0,0,0,2,2,2
]

,
[
2,4,0,0,3,3,0,0,4,2,
0,0,2,2,2,2,2,2,0,0,
4,5,5,4,5,5,4,5,5,4,
1,0,4,0,1,1,0,4,0,1,
1,1,1,1,1,1,1,1,1,1
]

];

let ballMap = new Array();
let blockMap = new Array();
let missileMap = new Array();
let capselMap = new Array();
let player;

let debug = "";

class Player{
	constructor(x,y,width=100) {
		this.set(x,y,width);
		this.autoPilot= false;
	}
	
	set(x,y,width) {
		this.x = x;
		this.y = y;
		this.Width = width;
		this.playerWidthMAX = width;
		this.playerWidthPlus =0;

		this.Dir_migi = 0;
		this.Dir_hidari = 0;

		this.Speed = DEF_BALLSPEED;

		this.missilemode=false;
		this.ballmode=false;

		this.effectCnt = DEF_EFFECTCNT_P;
		
	}
	
	setWidth(w) {
		this.Width = w;
		this.playerWidthMAX = this.Width;
	}
	setWidthPlus() {
		this.playerWidthPlus = this.Width;
		this.playerWidthMAX = this.Width;
	}
	
	draw(){
		this.Width = this.playerWidthMAX + this.playerWidthPlus;

		if(this.autoPilot) {
			if(ballMap.length > 0) {
				this.x = ballMap[0].x - (this.Width>>1);
			}
		} else {
			this.x += (this.Dir_hidari + this.Dir_migi )*this.Speed;
		}
		
		if(this.x < -(this.Width>>1)) {
			this.x = -(this.Width>>1);
		}
		if(this.x + (this.Width>>1) > canvas.width) {
			this.x = canvas.width - (this.Width>>1);
		}

		context.beginPath();
		if(this.missilemode == true) {
			context.fillStyle = "yellow";
			if(this.effectCnt % 30 == 0 ){
				addMissile();
			}
		} else {
			context.fillStyle = "red";
		}

		context.fillRect(this.x , this.y , this.Width , 10);
		context.fillStyle = "white";
		context.fillRect(this.x + this.Width*0.1 , this.y , this.Width*0.8 , 10);
		context.closePath();
		
		//アイテム効果をなくす
		if(this.playerWidthPlus > 0) {
			this.playerWidthPlus = this.playerWidthPlus - 0.2;
		} else {
			this.playerWidthPlus = 0;
		}
		
		this.effectCnt--;
		if(this.effectCnt <= 0 ){
			this.missilemode=false;
			this.ballmode=false;
			this.effectCnt = DEF_EFFECTCNT_P;
		} 
	}
	
}

class Ball{
    constructor( x,y ,dirx,diry) {
        this.x = x;
        this.y = y;
        this.flag = true; //有効フラグ
        this.DirX = dirx; //移動方向
        this.DirY = diry; //移動方向
        this.ballSpeed = DEF_BALLSPEED;
        this.ballSize = def_ballSize;
        this.blockCollisionCnt = 0; //ブロックに当たった数（パドルに当たるとリセット）
		this.ballColor = "white";
		this.effectCnt = DEF_EFFECTCNT;
	}

	move() {
		if(this.flag == false) return;
		
		this.x += this.DirX * this.ballSpeed;
		this.y += this.DirY * this.ballSpeed;
		
		//壁
		if((this.x  < 0 + this.ballSize) || (this.x  + this.ballSize > canvas.width )) {
			if(this.DirY>0) {
				this.DirY+= Math.random()*0.1;
			} else {
				this.DirY-= Math.random()*0.1;
			}
			this.DirX *= -1;
			if(this.x < this.ballSize) this.x = this.ballSize;
			if(this.x + this.ballSize > canvas.width ) this.x = canvas.width - this.ballSize;
			playsound(AUDIO_WALL);
		}
		if(this.y < (0 + this.ballSize)) {
			this.y = 1 + this.ballSize;
			this.DirY *= -1;
			if(this.DirX > 0) {
				this.DirX += Math.random()*0.1;
			} else {
				this.DirX -= Math.random()*0.1;
			}
			playsound(AUDIO_WALL);
		}
		
		//
		if(this.y > canvas.height) {//下に落ちた
			this.flag=false;
		}
		
		this.draw();
	}
	
	reverse() {
		this.x = this.x - (this.DirX * this.ballSpeed);
		this.y = this.y - (this.DirY * this.ballSpeed);
	}
	
	setBallSize(){
		this.ballSize  = def_ballSize<<2;
		this.effectCnt = DEF_EFFECTCNT;
	}

	draw() {
		this.effectCnt--;
		if(this.effectCnt <= 0 && this.ballSize > def_ballSize) {
			this.ballSize -= 0.5;
			this.effectCnt = DEF_EFFECTCNT;
		}
		if(this.ballSize < def_ballSize) {
			this.ballSize = def_ballSize;
		}

		context.beginPath();
		context.fillStyle = this.ballColor;
		context.arc(this.x ,this.y, this.ballSize, 0, 2 * Math.PI);
		context.fill();
		if(this.blockCollisionCnt>1) {
			context.font = "24px 'arial'";
			context.fillStyle = "red";
			context.fillText("x" + this.blockCollisionCnt , this.x+ this.ballSize ,this.y ,64);
		}
		context.closePath();
	}
	
	HitCheckPaddle(pX,pY,pW) {
		//パドルとの当たり判定
		if(this.DirY>0 && (pX<=this.x+this.ballSize)&&(pX+pW>=this.x)
			&&(pY<=this.y+this.ballSize)&&(pY+this.ballSize>=this.y)) {
			this.DirY *= -1;
			this.DirX += (Math.random()*2) - 1;
			this.speedrandom();
			this.blockCollisionCnt = 0;
			playsound(AUDIO_PADLE);
			return true;
		}
		return false;
	}


	cushion_tate() {
		this.reverse();
		this.DirY *= -1;
		this.DirX = (Math.random()+0.5) * ((Math.random() < 0.5) ? 1 : -1);
		
		if(this.DirX == 0) {//真横に動く不具合対策
			this.DirX = 1;
			this.DirY = 1; 
		}
		this.blockCollisionCnt+=1;
		this.speedup();
		playsound(AUDIO_PINGPONG);
	}


	cushion_yoko() {
		this.reverse();
		this.DirY = (Math.random()+0.5) * ((Math.random() < 0.5) ? 1 : -1);
		this.DirX *= -1;
		
		if(this.DirX == 0) {//真横に動く不具合対策
			this.DirX = 1;
			this.DirY = 1; 
		}
		this.blockCollisionCnt+=1;
		this.speedup();
		playsound(AUDIO_PINGPONG);
	}

	speedup() {
		this.speed+=Math.random()+0.5;
		if(this.speed > DEF_BALLSPEEDMAX) {
			this.speed = DEF_BALLSPEEDMAX;
		}
	}
	
	speeddown() {
		this.speed = this.speed / 2;
		if(this.speed < DEF_BALLSPEED) {
			this.speed = DEF_BALLSPEED;
		}
	}
	
	speedrandom() {
		if(this.speed > DEF_BALLSPEEDMAX/2) {
			this.speeddown();
		} else {
			this.speed = Math.random()*DEF_BALLSPEEDMAX;
		}
	}
}

class Missile extends Ball {
    constructor( x,y ,dirx,diry,rgba) {
    	super(x,y,dirx,diry);
    	this.ballColor = rgba;
    }

	move(){
		if(this.flag == false) return;
		this.y += this.DirY * this.ballSpeed;
		
		if(this.y <= 0) this.flag = false;

		this.draw();
	}
	
	draw() {
		context.beginPath();
		context.fillStyle = this.ballColor;
		context.moveTo(this.x, this.y-this.ballSize*2);
		context.lineTo(this.x+this.ballSize, this.y);
		context.lineTo(this.x-this.ballSize, this.y);
		
		context.fill();
		context.closePath();
	}
}

class Capsel extends Ball {
    constructor( x,y ,dirx,diry,rgba) {
    	super(x,y,dirx,diry);
    	this.ballColor = rgba;
    	this.ballSize *= 3;
    }

	HitCheckPaddle(pX,pY,pW) {
		if(super.HitCheckPaddle(pX,pY,pW)) {
			this.flag = false;
			
			if(this.ballColor == CAPSEL_COLOR_RED) {
				player.missilemode = true;
				player.effectCnt = DEF_EFFECTCNT_P;
//				document.getElementById("ball").value = "🚀";
				
			} else if(this.ballColor == CAPSEL_COLOR_GREEN) {
			
				player.setWidthPlus();
				
			} else if(this.ballColor == CAPSEL_COLOR_BULE) {
			
				for(let j=0;j<ballMap.length;j++) {
					ballMap[j].setBallSize();
				}
				
			} else if(this.ballColor == CAPSEL_COLOR_WHITE) {
			
				addBall();
				addBall();
				addBall();
			}
		}
	}
	
	move(){
		if(this.flag == false) return;
		this.y += this.DirY * this.ballSpeed;

		if(this.y == canvas.height) this.flag = false;
		
		this.draw();
	}

	draw() {
		context.beginPath();
		context.fillStyle = this.ballColor;
		context.fillRect(this.x ,this.y, this.ballSize*2, this.ballSize);
		context.fillStyle = "white";
		context.font = this.ballSize + "px 'arial black'";
		context.fillText("Pow" , this.x+5, this.y+this.ballSize ,this.ballSize);
		context.closePath();
	}
}

class Block{
	constructor( id,x,y ,w,h,status) {
        this.id = id;
        this.x = x;
        this.y = y;
        this.w = w;
        this.h = h;
        this.blockStatus = status; //硬さ
		this.effectCnt = 0;
	}

	draw() {
		if(this.blockStatus <= 0) {
			if(this.effectCnt <= 0){
				return false;
			}
			this.effectCnt--;
		}

		this.drawsquea(this.x,this.y,def_blockWidth,def_blockHeight,4,5,"#ffffee");

		context.textAlign = "start"; 
		context.textBaseline = "alphabetic";
		context.font = def_blockWidth*0.8 + "px 'Segoe UI Emoji','Segoe UI Symbol','Apple Color Emoji','Noto Color Emoji','Noto Emoji',sans-serif";
		context.fillText(BLOCKTEXT[this.blockStatus],this.x  ,this.y+(def_blockWidth*0.8) ,def_blockWidth);
		return true;
		
	}
	
	drawsquea(x,y,w,h,r,lineWidth,fillColor) {
		context.beginPath();
		context.fillStyle = "#5588aa";
		context.fillRect(x+lineWidth,y+lineWidth,w,h);
		context.lineWidth = lineWidth;
		context.fillStyle = fillColor;
		context.arc(x+r,y+h-r,r,Math.PI,Math.PI*0.5,true);
		context.arc(x+w-r,y+h-r,r,Math.PI*0.5,0,1);
		context.arc(x+w-r,y+r,r,0,Math.PI*1.5,1);
		context.arc(x+r,y+r,r,Math.PI*1.5,Math.PI,1);
		context.closePath();
		context.fill();
	}


	HitCheck(){
		let b = ballMap;
		if(this.draw() && this.blockStatus > 0) {
			//ブロックとボールの衝突判定をする
			for(let i=0;i<b.length;i++) {
				
				if ((this.x <= b[i].x + (b[i].ballSize/2)) && (this.x+this.w >= b[i].x - (b[i].ballSize/2))
					&& (this.y <= b[i].y + (b[i].ballSize/2)) && (this.y+this.h >= b[i].y - (b[i].ballSize/2))) {
					
					//カプセル
					let a = Math.floor(Math.random() * CAPSELBLOCK.length * 5);
					if(a < CAPSELBLOCK.length && capselMap.length < 3) {
						capselMap.push(new Capsel( this.x, this.y, 0,1,CAPSELBLOCK[a]) );
					} else if(a % 8 == 0) {
						addBall(b[i].x ,b[i].y ,0);
						addBall(b[i].x ,b[i].y ,0);
					}

					context.fillText(BLOCKTEXT[0],this.x  ,this.y+def_blockWidth ,def_blockWidth);

					let cx = this.x+this.w/2;
					let cy = this.y+this.h/2;
					let yoko = Math.abs(cx - b[i].x);
					let tate = Math.abs(cy - b[i].y);
					if(yoko > tate) {
					//横衝突
						b[i].cushion_yoko();
					} else {
					//縦衝突
						b[i].cushion_tate();
					} 
					
					score += 1 * b[i].blockCollisionCnt;
					
					this.blockStatus -= 1;
					if(this.blockStatus <= 0) {
						this.effectCnt = DEF_EFFECTCNT;
					}
				}
			}

			//衝突判定
			let m = missileMap;
			for(let i=0;i<m.length;i++) {	
				if ((this.x <= m[i].x + (m[i].ballSize/2)) && (this.x+this.w >= m[i].x - (m[i].ballSize/2))
					&& (this.y+this.h >= m[i].y )) {

					this.blockStatus -= 1;
					m[i].flag = false;
					if(this.blockStatus <= 0) {
						this.effectCnt = DEF_EFFECTCNT;
					}
				}
			}

			return true;
		}
		return false;
	}


}

        
class TimerCtl {
	constructor( func,interval) {
        this.func = func;
        this.interval = interval;
        this.hdl = null;
        this.starttime =0;
	}
	
	start() {
		if(this.hdl != null) stop();
		
		let now = new Date();
		this.starttime = now.getTime();
		if(this.func != null) {
			this.hdl = setInterval(this.func,this.interval);
		}
	}
	
	stop() {
		if(this.hdl != null) {
			clearInterval(this.hdl);
		}
		this.hdl = null;
	}
	
	setFunc(f) {
		this.func = f;
	}
	
	getCounterSec() {
		let now = new Date();
		let ms =  now.getTime() - this.starttime;
		return (Math.floor(ms/1000));
	}
}
var timerCtl;

const init = function(){
	loadRank();
	
	canvas = document.getElementById("game");
	context = canvas.getContext("2d");
	
	player = null;
	player = new Player(0,0);
	setSize();


	context.clearRect(0,0,canvas.width,canvas.height);
	//
	
	score = 0;
	stage = 0;


	drawStage();
	player.set( (canvas.width/2) - (player.Width/2), canvas.height*0.95, player.Width);
	player.draw();


	document.getElementById("popup").style.display = "block";
	document.getElementById("popup_text").innerHTML = "<h3>どうぶつ崩し２</h3>";
	//1から3
	let ranking="";
	for(let i=0; i<3; i++) {
		ranking += "Rank " + (i+1) + " :" + rank_score[i] + "<br>";
	}
	document.getElementById("popup_text").innerHTML += ranking;
	document.getElementById("popup_start").value = "遊ぶ";
	
	// スクロール禁止(SP)
	document.addEventListener('touchmove', noScroll, { passive: false });
	// スクロール禁止(PC)
//	document.addEventListener('mousewheel', noScroll, { passive: false });


//	if ('ontouchend' in document) {
	    EVENTNAME_TOUCHSTART = 'touchstart';
	    EVENTNAME_TOUCHMOVE = 'touchmove';
	    EVENTNAME_TOUCHEND = 'touchend';
//	} else {
	    EVENTNAME_TOUCHSTART2 = 'mousedown';
	    EVENTNAME_TOUCHMOVE2 = 'mousemove';
	    EVENTNAME_TOUCHEND2 = 'mouseup';
//	}

	document.getElementById("hidari").addEventListener(EVENTNAME_TOUCHSTART,function(e) {e.preventDefault();hidari(e);});
	document.getElementById("hidari").addEventListener(EVENTNAME_TOUCHEND,function(e) {e.preventDefault();touchend_hidari(e);});
	document.getElementById("migi").addEventListener(EVENTNAME_TOUCHSTART,function(e) {e.preventDefault();migi(e);});
	document.getElementById("migi").addEventListener(EVENTNAME_TOUCHEND,function(e) {e.preventDefault();touchend_migi(e);});
	document.getElementById("hidari").addEventListener(EVENTNAME_TOUCHSTART2,function(e) {e.preventDefault();hidari(e);});
	document.getElementById("hidari").addEventListener(EVENTNAME_TOUCHEND2,function(e) {e.preventDefault();touchend_hidari(e);});
	document.getElementById("migi").addEventListener(EVENTNAME_TOUCHSTART2,function(e) {e.preventDefault();migi(e);});
	document.getElementById("migi").addEventListener(EVENTNAME_TOUCHEND2,function(e) {e.preventDefault();touchend_migi(e);});

	document.getElementById("auto").addEventListener(EVENTNAME_TOUCHSTART,function(e) {e.preventDefault();auto(e);});
	document.getElementById("auto").addEventListener(EVENTNAME_TOUCHSTART2,function(e) {e.preventDefault();auto(e);});

	canvas.addEventListener(EVENTNAME_TOUCHSTART, function(e) {e.preventDefault();
        canvas_touch(e,true);},false);
	canvas.addEventListener(EVENTNAME_TOUCHMOVE, function(e) {e.preventDefault();
        canvas_touch(e,true);},false);
	canvas.addEventListener(EVENTNAME_TOUCHEND, function(e) {e.preventDefault();
        canvas_touch(e,false);},false);

	canvas.addEventListener(EVENTNAME_TOUCHSTART2, function(e) {e.preventDefault();touchflag=true;
        canvas_touch_mouse(e,true);},false);
	canvas.addEventListener(EVENTNAME_TOUCHMOVE2, function(e) {e.preventDefault();
        if(touchflag){canvas_touch_mouse(e,true);}},false);
	canvas.addEventListener(EVENTNAME_TOUCHEND2, function(e) {e.preventDefault();touchflag=false;
        canvas_touch_mouse(e,false);},false);



	timerCtl = new TimerCtl(null,499,60000);
}

function canvas_touch(e,mode) {
	if(mode == false) {
		//指が離れた
		let len = e.changedTouches.length;
		for (let i = 0; i<len; i++) {
			let touch = e.changedTouches.item(i);
			let touchX = touch.pageX;
			console.log(i+"  "+touchX);
			if(touchX < player.x + (player.Width/2) ) { //left
				player.Dir_hidari = 0;
			}
			if(touchX > player.x + (player.Width/2) ){ //right
				player.Dir_migi = 0;
			}
		}
		
		return;
		
		//changedTouches
	}

	let hidari=0;
	let migi=0;
	
	let len = e.targetTouches.length;
	for (let i = 0; i<len; i++) {
		let touch = e.targetTouches.item(i);
		let touchX = touch.pageX;
		console.log(i+"  "+touchX);
		if(touchX < player.x + (player.Width/2) ) { //left
			hidari = hidari -1;
		}
		if(touchX > player.x + (player.Width/2) ){ //right
			migi = migi + 1;
		}
	}

	player.Dir_hidari = hidari;
	player.Dir_migi = migi;
}

function canvas_touch_mouse(e,mode,tmp) {
	if(mode == false) {
		player.Dir_hidari = 0;
		player.Dir_migi = 0;
		return;
	}
	
	let touchX;
	touchX = e.clientX - canvas.getBoundingClientRect().left;
	if(touchX < player.x + (player.Width/2) ) { //left
		player.Dir_hidari = -1;
		player.Dir_migi = 0;

	} else if(touchX > player.x + (player.Width/2) ){ //right
		player.Dir_migi = 1;
		player.Dir_hidari = 0;

	}

}


const setSize = function() {
	if(window.innerWidth < 640) {
		canvas.width = window.innerWidth*0.95;
	} else {
		canvas.width = 640;
	}
	
	if(window.innerHeight >= 480) {
		canvas.height = window.innerHeight*0.8;
		def_blockWidth = canvas.width/14;
		def_blockHeight = canvas.width/14;
	} else {
		canvas.width = 640 * ((window.innerWidth*0.75) / 480);
		canvas.height = window.innerHeight*0.75;
		def_blockWidth = canvas.height/14;
		def_blockHeight = canvas.height/14;
	}
	
	player.setWidth(canvas.width / 5);

}

const noScroll = function(event) {
	event.preventDefault();
}

const startGame = function() {
	document.getElementById("popup").style.display = "none";

	if(status == "ready" || status == "next" ) {
		//

		
		//
		status = "start";
		
		setSize();
		
		player.set((canvas.width/2) - (player.Width/2) , canvas.height*0.95 , player.Width);

		//
		ballMap.length = 0;
		ballMap.push(new Ball(player.x,canvas.height*0.65,0.5,1));
		
		missileMap.length = 0;
		capselMap.length = 0;
		
		//
		if(stage == 0) {
			score = 0;
		}
		//
		drawStage();
		player.draw();
		
//		document.getElementById("ball").value = "　";
		//
		window.requestAnimationFrame(main);
		timerCtl.start();

		playsound(AUDIO_BGM);
	} else {
		//
//		status = "end";
	}
}

const drawStage = function() {
	//
	blockMap.length = 0;

	for(var i=0;i<STAGEDATA[stage].length;i++) {
		blockMap.push(new Block( i
						,(i%10)*def_blockWidth*1.2+def_blockHeight
						,Math.floor(i/10)*def_blockHeight*1.2+def_blockWidth/2
						,def_blockWidth
						,def_blockHeight
						,STAGEDATA[stage][i]
						));
		blockMap[i].draw();
	}
	
}


const main = function() {
	//
	context.clearRect(0,0,canvas.width,canvas.height);

	//

	player.draw();

	//

	for(let i=0;i<ballMap.length;i++) {
		if(ballMap[i].flag == true) {
			ballMap[i].move();
			ballMap[i].HitCheckPaddle(player.x , player.y , player.Width);
		} else {
		//下に落ちたらリストからボールを消す
			ballMap.splice(i,1);
			i--;
		}
	}
	
	if(ballMap.length==0) {
		status = "end";
	}

	//missileMap
	for(let i=0;i<missileMap.length;i++) {
		if(missileMap[i].flag==true){
			missileMap[i].move();
			//衝突チェックはブロックの描画時に行う
		}else{
			missileMap.splice(i,1);
			i--;
		}
	}

	//capselMap
	for(let i=0;i<capselMap.length;i++) {
		if(capselMap[i].flag==true){
			capselMap[i].move();
			capselMap[i].HitCheckPaddle(player.x , player.y , player.Width);
		}else{
			capselMap.splice(i,1);
			i--;
		}
	}

	//blockMap
	var cnt = 0;
	for(let j=0;j<blockMap.length;j++) {
		if(blockMap[j].HitCheck()) {
			cnt+=1;
		}
	}

	Clock();
	//
	if((status == "end") || (cnt==0)) {
		timerCtl.stop();
		playsound(AUDIO_BGM,false);
		//
		context.font = "36px 'arial black'";
		context.fillStyle = "red";
		context.textAlign = "center";
		let message="",message2="";
		let btnText;
		
		if(cnt==0){
			if(stage == STAGEDATA.length-1) {
				message = "Congratulations!";
				btnText = "もう一度遊ぶ";
				status = "ready";
				stage = 0;
				message2 = RankingStr();
			} else {
				message = "Clear!";
				btnText = "Next";
				status = "next";
				stage+=1;
			}
		} else {
			message = "GAME OVER";
			btnText = "もう一度遊ぶ";
			status = "ready";
			stage = 0;

			message2 = RankingStr();
		}
		
		document.getElementById("popup").style.display = "block";
		document.getElementById("popup_text").innerHTML = message + "<br><br>";
		document.getElementById("popup_text").innerHTML += message2;
		document.getElementById("popup_start").value = btnText;

//		context.fillText(message,canvas.width/2,canvas.height/2);
		
		//
		//
	} else {
		window.requestAnimationFrame(main);
	}
}

const Clock = function() {
	//document.getElementById("info").innerHTML  = "STAGE:"+(stage+1)+"  SCORE:" + score;
	//return;
	let str;
	str = "STAGE:"+(stage+1)+"  SCORE:" + score;
	if(debug != "") {
		str += debug;
	}

	context.beginPath();
	context.font = "20px 'arial black'";
	context.fillStyle = "red";
	context.fillText(str,10,20,300);
	context.closePath();
};

document.onkeydown = function(e) {
	//
	if((e.key == "ArrowLeft")||(e.key == "Left")) {
		//
		hidari();
		//player.Dir_hidari = -1;

	} else if((e.key == "ArrowRight")||(e.key == "Right")) {
		//
		migi();
		//player.Dir_migi = 1;
	}

}

document.onkeyup = function(e) {	
	//
	if ((e.key == "ArrowLeft")||(e.key == "Left")) {
		touchend_hidari(e);
		//player.Dir_hidari = 0;
	} else if((e.key == "ArrowRight")||(e.key == "Right")) {
		touchend_migi(e);
		//player.Dir_migi = 0;
	} else if((e.key == "ArrowUp")||(e.key == "Up")) {
		if(player.missilemode == true) {
			addMissile();
		}
	} else if((e.key == "ArrowDown")||(e.key == "Down")) {
		if(player.ballmode == true){
			addBall();
		}
	}


}

const addBall = function(px,py,pw) {
	if(ballMap.length >= BALLCNTMAX || status != "start") return;

	let x,y,w;
	if(px===undefined) {
		x = player.x;
		y = player.y;
		w = player.Width;
		dx = (Math.random()*2)-1;
		dy = -1;
	} else {
		x = px;
		y = py;
		w = pw;
		dx = (Math.random()*2)-1;
		dy = (Math.random()*2)-1;
	}

	ballMap.push(new Ball(x+(w/2) , y , (Math.random()*2)-1 , dy));
	ballMap[ballMap.length-1].draw();
	ballMap[ballMap.length-1].speedrandom();
}

const addMissile = function() {
	if(status != "start") return;

	let x,y,w;

	x = player.x;
	y = player.y;
	w = player.Width;
	dx = 0;
	dy = -1;

	
	if(missileMap.length > 1) {
		if(y-def_blockWidth > missileMap[missileMap.length-1].y) {
			missileMap.push(new Missile(x+(w/2) , y , dx , dy ,"blue"));
		}
	} else {
		missileMap.push(new Missile(x+(w/2) , y , dx , dy ,"blue"));
	}
}

const add = function() {
	if(player.missilemode == true) {
		addMissile();
	} else if(player.ballmode == true){
		addBall();
	}
}

const auto = function(e) {
	if(player.autoPilot == false) {
		player.autoPilot = true;
		document.getElementById("auto").style.background = "#FF0000";
	} else {
		player.autoPilot = false;
		document.getElementById("auto").style.background = "#0000FF";
	}
}

const RankingStr = function() {
	let ret = "";
	
	for(let i=0; i<3; i++) {
		if(rank_score[i]<=score) {
			let j=1;
			while(j>=i) {
				rank_score[j+1] = rank_score[j];
					j--;
			}
			rank_score[i] = score;
			i=3;
		}
	}
	saveRank();
	for(let i=0; i<3; i++) {
		if(rank_score[i] == score ) {
			ret += "👑<font color=red>Rank " + (i+1) + " :" + rank_score[i] + "</font><br>";
			score++;
		} else {
			ret += "Rank " + (i+1) + " :" + rank_score[i] + "<br>";
		}
	}
	return ret;
}

const saveRank = function() {
	//rankingの書き込み
	for(let i=0;i<3;i++) {
		//ＪＳＯＮ形式に変換
		let scoreJSON = {
			"score":rank_score[i],
			"time":rank_time[i],
		};

		//文字列に変換
		let scoreString = JSON.stringify(scoreJSON);
		//ローカルストレージに保存
		localStorage.setItem(STORAGESTRINGS+i,scoreString);
	}
}

const loadRank = function() {
	//rankingの読み込み
	let max =0;
	for(let i=0; i<3; i++) {
		//ローカルストレージから読み込み
		let scoreString = localStorage.getItem(STORAGESTRINGS+i);
		rank_score[i] = (3-i)*10;//初期値として100を入れる
		rank_time[i] = 0;
		if (scoreString != null) {
			let scoreJSON = JSON.parse(scoreString);
			rank_score[i] = scoreJSON.score;
			rank_time[i] = scoreJSON.time;
		}
	}
}

const AUDIO_BGM=0,AUDIO_PINGPONG=1,AUDIO_BOO=2,AUDIO_PADLE=3,AUDIO_WALL=4;
const playsound = function(num,flag=true) {
	let id_str = "";
	
	switch(num) {
		case AUDIO_BGM:
			id_str = "bgm";
		break;
		case AUDIO_PINGPONG:
			id_str = "oto01";//https://soundeffect-lab.info/sound/button/
		break;
		case AUDIO_PADLE:
			id_str = "oto02";//https://soundeffect-lab.info/sound/button/
		break;
		case AUDIO_WALL:
			id_str = "oto03";//https://soundeffect-lab.info/sound/button/
		break;
	}
	
	if(id_str == "") return;
	
	if(flag) {
		document.getElementById(id_str).currentTime = 0;
		document.getElementById(id_str).volume  = 0.6;
		document.getElementById(id_str).play();
	} else {
		document.getElementById(id_str).pause();
	}
}

const hidari = function(e){
	//e.preventDefault();
	document.getElementById("hidari").style.color ="red";
	document.getElementById("hidari").style.borderColor ="red";
	player.Dir_hidari = -1;
}

const migi = function(e){
	//e.preventDefault();
	document.getElementById("migi").style.color ="red";
	document.getElementById("migi").style.borderColor ="red";
	player.Dir_migi = 1;
}

const touchend_migi = function(e){
	//e.preventDefault();
	document.getElementById("migi").style.color ="#FFFFFF";
	document.getElementById("migi").style.borderColor ="#0000FF";
	player.Dir_migi = 0;
}

const touchend_hidari = function(e){
	//e.preventDefault();
	document.getElementById("hidari").style.color ="#FFFFFF";
	document.getElementById("hidari").style.borderColor ="#0000FF";
	player.Dir_hidari = 0;
}

</script>
<style>


body {
	background:url("background02.jpg");
	background-size: cover;
	font-family: "Segoe UI Emoji","Segoe UI Symbol","Apple Color Emoji","Noto Color Emoji","Noto Emoji",sans-serif;
	
	
user-select:none;
-webkit-user-select:none;
-ms-user-select: none;
-moz-user-select:none;
-khtml-user-select:none;
-webkit-user-drag:none;
-khtml-user-drag:none;

	
}
#title {
    display:inline-block;
	color:#000;
	font-size:calc(100vh / 35);
    font-weight:bold;
	text-align:center;
}

#info,#stage {
    display:inline-block;
    font-size:calc(100vh / 35);
}

#game {
	background-color:rgba(0,128,128,0.4);
    font-weight:bold;
	font-family: "Segoe UI Emoji","Segoe UI Symbol","Apple Color Emoji","Noto Color Emoji","Noto Emoji",sans-serif;
	
}

#gamegamen {
	text-align:center;
}

.raised{
    display:inline-block;
    width:calc(100vw / 3.5);
    height:calc(100vh / 15);
    line-height:120%;
    text-decoration: none;
    background:#0000FF;
    text-align:center;
    border:5px solid #0000FF;
    color:#FFFFFF;
    font-size:calc(100vh / 30);
    font-weight:bold;
    border-radius:30px;
    -webkit-border-radius:30px;
    -moz-border-radius:30px;
    transition: all 0.5s ease;
}

.Btn {
    display:inline-block;
    width:calc(100vw / 5);
    height:calc(100vh / 25);
    line-height:100%;
    text-decoration: none;
    background:#0000FF;
    text-align:center;
    border:5px solid #0000FF;
    color:#FFFFFF;
    font-size:calc(100vh / 42);
    border-radius:5px;
    -webkit-border-radius:5px;
    -moz-border-radius:5px;
    align-items: center;
    margin:6px;
}

.Btn2 {
    display:inline-block;
    width:calc(100vw / 3);
    height:calc(100vh / 25);
    line-height:100%;
    text-decoration: none;
    background:#0000FF;
    text-align:center;
    border:5px solid #0000FF;
    color:#FFFFFF;
    font-size:calc(100vh / 42);
    border-radius:5px;
    -webkit-border-radius:5px;
    -moz-border-radius:5px;
    align-items: center;
    margin:6px;
}

#ball {
    width:calc(100vw / 5);
    font-size:calc(100vh / 40);
}


div#popup {
    position:fixed;
	display: none;

	top: 15%;
	left:1%;
	width:99%;
	text-align: center;
    background-color:#88ff88;
	font-size: 1.5rem;

	filter:alpha(opacity=85);
	-moz-opacity: 0.85;
	opacity: 0.85;
	
	padding: 5px;
}


</style>
</head>
<body onload="init()" onSelectStart="return false;">
<div id="gamegamen">
	<div id="info"></div><br>
	<canvas id="game"></canvas>
	<br>
	<input type="button" id="hidari" class="raised" value="左">
	<input type="button" id="auto" class="Btn" value="auto">
	<input type="button" id="migi" class="raised"  value="右">
	<br>
</div>
<div id="popup">
	<span id="popup_text"></span>
	<p><input type="button" id="popup_start" class="Btn2" value="遊ぶ" onclick="startGame()"></p>
</div>
<audio id="bgm" src="bgm_block2.mp3" preload="auto" loop type="audio/mp3"></audio>
<audio id="oto01" src="cursor1.mp3" preload="auto" type="audio/mp3"></audio>
<audio id="oto02" src="decision3.mp3" preload="auto" type="audio/mp3"></audio>
<audio id="oto03" src="decision1.mp3" preload="auto" type="audio/mp3"></audio>

</body>
</html>
