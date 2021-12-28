// keeps one version of our canvas for reference
var canvasState;
var game;

var listener = false;

// sound effects
var chatDing = new Audio("/files/chat_ding.mp3");
var winMusic = new Audio("/files/extreme_ways.mp3");
var tileDrop = new Audio("/files/tile_drop.mp3");
var turnChange = new Audio("/files/turn_change.mp3");
var quitAudio  = new Audio("/files/quit_game.mp3");
var gameStart = new Audio("/files/start_game.mp3");
var impossibleMusic  = new Audio("/files/impossible.mp3");

var winAudioPlayed = false;
var impossibleAudioPlayed = false;
var quitAudioPlayed = false;

// images for tiles
var lineReady = false;
var straightPath = new Image();
straightPath.src = "/img/straight_line.jpg";

var curveReady = false;
var curveTile = new Image();
curveTile.src = "/img/curve.jpg";

// background images
var bgStationLeftReady = false;
var stationLeft = new Image();
stationLeft.src = "/img/station_left.jpg";

var bgStationRightReady = false;
var stationRight = new Image();
stationRight.src = "/img/station_right.jpg";

var cellSize = 50;

// starting stations
var leftStation, rightStation;

var bw = 600;
var bh = 600;

var p = 0;

var turnPolling = false;

var NINETY_DEGREES = 90 * Math.PI / 180;
var ONE_EIGHT_DEGREES = 180 * Math.PI / 180;
var TWO_SEVENTY_DEGREES = 270 * Math.PI / 180;

var RIGHT = 1;
var LEFT = 2;
var UP = 3;
var DOWN = 4;

// maximum tiles available in the game
var MAX_TILES = 16;

// constants for tiles
var STATION_LEFT_TILE = 0;
var STATION_RIGHT_TILE = 1;
var STRAIGHT_TILE = 2;
var CURVE_TILE = 3;

// maximum tiles that can be used in a turn
var MAX_TURN_TILES = 3;

$(document).ready(function() {
  straightPath.onload = function () {
    lineReady = true;
  };
  curveTile.onload = function () {
    curveReady = true;
  };
  stationLeft.onload = function () {
    bgStationLeftReady = true;
    if(canvasState) canvasState.valid = false;
  };
  stationRight.onload = function () {
    bgStationRightReady = true;
    if(canvasState) canvasState.valid = false;
  };  
});

function showRules() {
  if($("#how-to-play").css("display") == "none") {
    $("#how-to-play").show();  
  }
  else {
    $("#how-to-play").hide();
  }
}

function quitGame() {
  bootbox.confirm({
      message: 'Are you sure you want to quit this game?',
      buttons: {
          'cancel': {
              label: '<span>Cancel</span>',
              className: 'btn'
          },
          'confirm': {
              label: '<span>Yes</span>',
              className: 'btn'
          }
      },
      callback: function(result) {
          if(result) {
            performQuit();
          }
      }
  });
}

function performQuit() {
  $.getJSON("/pages/quit/" + gameObject.Game.game_key, function(data) {
    if(data.result == "FAILURE") {
        alert(data.message);
    }
    else {
      window.location.href = "/pages/home";
    }
  });
}

function playAgain() {
  if(isPlayer1) {
    window.location.href = "/pages/startNewGame";  
  }
  else {
    performQuit();
  }
}

// Constructor for Tile objects to hold data for all drawn objects.
// For now they will just be defined as rectangles.
function Tile(x, y, w, h, shapeType, moveable, angleInRadians) {
  this.x = x || 0;
  this.y = y || 0;
  this.w = w || 1;
  this.h = h || 1;
  this.angleInRadians = angleInRadians || 0;
  
  this.shapeType = shapeType;

  if(moveable != undefined) {
    this.moveable = moveable;  
  }
  else {
    this.moveable = true;
  }
}

// Draws this shape to a given context
Tile.prototype.draw = function(ctx) {
  ctx.save();

  // move to the center of where we want to draw the tile
  ctx.translate(this.x + this.w / 2, this.y + this.h / 2);

  // rotate the image by the angle  
  ctx.rotate(this.angleInRadians);

  // draw image
  if(lineReady && this.shapeType == STATION_LEFT_TILE) {
    ctx.drawImage(stationLeft, -1 * this.w / 2, -1 * this.h / 2, this.w, this.h);    
  }
  else if(lineReady && this.shapeType == STATION_RIGHT_TILE) {
    ctx.drawImage(stationRight, -1 * this.w / 2, -1 * this.h / 2, this.w, this.h);    
  }
  else if(lineReady && this.shapeType == STRAIGHT_TILE) {
    ctx.drawImage(straightPath, -1 * this.w / 2, -1 * this.h / 2, this.w, this.h);     
  }
  else if(curveReady && this.shapeType == CURVE_TILE) {
    ctx.drawImage(curveTile, -1 * this.w / 2, -1 * this.h / 2, this.w, this.h);  
  }

  // restore everything
  ctx.restore();
}

// Forces this object into a grid cell.
Tile.prototype.snapToGrid = function(ctx, shapes) {
  var x = this.x;
  var y = this.y;
  var direction = 1;
  var foundConflict = true;

  while(foundConflict) {
    foundConflict = false;

    var left = Math.round(x / cellSize) * cellSize;
    var top = Math.round(y / cellSize) * cellSize;

    // make sure we are not putting the tile on top of another one
    for(var i = 0; i < shapes.length; i++) {
      if(shapes[i] != this && shapes[i].contains(left+1, top+1)) {
        if(x + cellSize < bw && direction) {
          x += cellSize;  
        }
        else if(x - cellSize > 0) {
          direction = 0;
          x -= cellSize;
        }
        else if(y + cellSize < bh && !direction) {
          y += cellSize;
          direction = 1;  
        }
        else if(y - cellSize > 0) {
          y -= cellSize;
        }

        foundConflict = true;

        break;
      }
    }
  }

  this.x = left;
  this.y = top;
}

// Determine if a point is inside the shape's bounds
Tile.prototype.contains = function(mx, my) {
  return  (this.x <= mx) && (this.x + this.w >= mx) &&
          (this.y <= my) && (this.y + this.h >= my);
}

function CanvasState(canvas) {
  // setup the canvas 
  this.canvas = canvas;
  this.width = canvas.width;
  this.height = canvas.height;
  this.ctx = canvas.getContext('2d');

  // This complicates things a little but but fixes mouse co-ordinate problems
  // when there's a border or padding. See getMouse for more detail
  var stylePaddingLeft, stylePaddingTop, styleBorderLeft, styleBorderTop;
  if (document.defaultView && document.defaultView.getComputedStyle) {
    this.stylePaddingLeft = parseInt(document.defaultView.getComputedStyle(canvas, null)['paddingLeft'], 10)      || 0;
    this.stylePaddingTop  = parseInt(document.defaultView.getComputedStyle(canvas, null)['paddingTop'], 10)       || 0;
    this.styleBorderLeft  = parseInt(document.defaultView.getComputedStyle(canvas, null)['borderLeftWidth'], 10)  || 0;
    this.styleBorderTop   = parseInt(document.defaultView.getComputedStyle(canvas, null)['borderTopWidth'], 10)   || 0;
  }
  // Some pages have fixed-position bars (like the stumbleupon bar) at the top or left of the page
  // They will mess up mouse coordinates and this fixes that
  var html = document.body.parentNode;
  this.htmlTop = html.offsetTop;
  this.htmlLeft = html.offsetLeft;

  // keep track of state
  this.valid = false; // when set to false, the canvas will redraw everything
  this.shapes = [];  // the collection of things to be drawn
  this.dragging = false; // Keep track of when we are dragging

  // the current selected object
  this.selection = null;
  this.dragoffx = 0; // See mousedown and mousemove events for explanation
  this.dragoffy = 0;
  
  // add event listeners

  // This is our reference to the canvas state for our closures
  var myState = this;
  
  //fixes a problem where double clicking causes text to get selected on the canvas
  canvas.addEventListener('selectstart', function(e) { e.preventDefault(); return false; }, false);

  addEventListener("keydown", function (e) {
    if(e.keyCode == 68) { // letter d
      myState.removeTile(myState.selection);
    }
    else if(e.keyCode == 82) { // letter r
      myState.rotateTile(myState.selection);
    }
  });

  // Up, down, and move are for dragging
  canvas.addEventListener('mousedown', function(e) {
    var mouse = myState.getMouse(e);
    var mx = mouse.x;
    var my = mouse.y;
    var shapes = myState.shapes;
    var l = shapes.length;
    for (var i = l-1; i >= 0; i--) {
      if (shapes[i].contains(mx, my) && shapes[i].moveable) {
        var mySel = shapes[i];
        // Keep track of where in the object we clicked
        // so we can move it smoothly (see mousemove)
        myState.dragoffx = mx - mySel.x;
        myState.dragoffy = my - mySel.y;
        myState.dragging = true;
        myState.selection = mySel;
        myState.valid = false;
        return;
      }
    }

    // console.log(myState.selection.angleInRadians);

    // havent returned means we have failed to select anything.
    // If there was an object selected, we deselect it
    if (myState.selection) {
      myState.selection = null;
      myState.valid = false; // Need to clear the old selection border
    }
  }, true);
  canvas.addEventListener('mousemove', function(e) {
    if (myState.dragging){
      var mouse = myState.getMouse(e);
      // we don't want to drag the object by its top-left corner, we want to drag it
      // from where we clicked. Thats why we saved the offset and use it here
      myState.selection.x = mouse.x - myState.dragoffx;
      myState.selection.y = mouse.y - myState.dragoffy;   
      myState.valid = false; // something's dragging so we must redraw
    }
  }, true);
  canvas.addEventListener('mouseup', function(e) {
    // if we have a selection, make sur the tile is placed in a valid location
    if(myState.selection != null) {
      try {
        myState.selection.snapToGrid(this.ctx, myState.shapes);  
      } catch(e) {}

      //console.log("win: " + game.isWin());
      
      myState.valid = false;
    }
    myState.dragging = false;
  }, true);
  // double click - rotate tile by 90 degrees
  canvas.addEventListener('dblclick', function(e) {
    if(myState.selection != null) {
      myState.rotateTile(myState.selection);
    }
  }, true);
  
  // **** Options! ****
  this.selectionColor = '#CC0000';
  this.selectionWidth = 2;  
  this.interval = 30;

  setInterval(function() { myState.draw(); }, myState.interval);
}

// rotates the shape if it is a moveable object
CanvasState.prototype.rotateTile = function(shape) {
  if(shape && shape.moveable) {
    shape.angleInRadians += 90 * Math.PI / 180;

    // reset angle
    if(shape.angleInRadians >= Math.PI * 2) {
      shape.angleInRadians = 0;
    }
    
    this.valid = false;
  }
}

// removes the given shape from the shape array
CanvasState.prototype.removeTile = function(shape) {
  if(shape && shape.moveable) {
    var index = this.shapes.indexOf(shape);
    if(index > -1) {
      this.shapes.splice(index, 1);
      game.totalTiles++;
      game.currentTile--;

      this.selection = false;
      this.valid = false;
    }
  }  
}

// adds a new shape to our shape array
CanvasState.prototype.addTile = function(shape) {
  this.shapes.push(shape);
  this.valid = false;
}

CanvasState.prototype.clear = function() {
  // this.ctx.clearRect(0, 0, this.width, this.height);
  // Store the current transformation matrix
  this.ctx.save();

  // Use the identity matrix while clearing the canvas
  this.ctx.setTransform(1, 0, 0, 1, 0, 0);
  this.ctx.clearRect(0, 0, this.width, this.height);

  // Restore the transform
  this.ctx.restore();
}

// while draw is called as often as the INTERVAL variable demands,
// it only ever does something if the canvas gets invalidated by our code
CanvasState.prototype.draw = function() {
  // if our state is invalid, redraw and validate!
  if (!this.valid) {
    //console.log("redraw");

    var ctx = this.ctx;
    var shapes = this.shapes;
    this.clear();

    // update titles left
    if(game.totalTiles == 1) {
      $("#pieces-left").html(game.totalTiles + " Tile Left");
    }
    else {
      $("#pieces-left").html(game.totalTiles + " Tiles Left");  
    }

    // show any relevant game state messages
    this.initGameStateMessages();

    // initialize the player's button states
    this.initButtons();
    
    // render background grid
    this.drawGrid();
    
    // draw all shapes
    var l = shapes.length;
    for (var i = 0; i < l; i++) {
      var shape = shapes[i];

      // We can skip the drawing of elements that have moved off the screen:
      if (shape.x > this.width || shape.y > this.height ||
          shape.x + shape.w < 0 || shape.y + shape.h < 0) continue;
      shapes[i].draw(ctx);
    }
    
    // draw selection
    // right now this is just a stroke along the edge of the selected Tile
    if (this.selection != null) {
      ctx.strokeStyle = this.selectionColor;
      ctx.lineWidth = this.selectionWidth;
      var mySel = this.selection;
      ctx.strokeRect(mySel.x,mySel.y,mySel.w,mySel.h);
    }
     
    this.valid = true;
  }
}

CanvasState.prototype.initGameStateMessages = function() {
  if(gameObject.Game.winner > 0) {
    if(isPlayer1) {
      if(gameObject.Game.winner == 1) {
        $("#player-info").html("Winner, congratulations!<br><br>Want to play again?<br><br><a href=\"#\" id=\"yes-link\">Yes</a> / <a href=\"#\" id=\"no-link\">No</a>");
      }
      else {
        $("#player-info").html("Sorry, Player 2 has won.<br><br>Want to play again?<br><br><a href=\"#\" id=\"yes-link\">Yes</a> / <a href=\"#\" id=\"no-link\">No</a>");
      }
    }
    else {
      if(gameObject.Game.winner == 1) {
        $("#player-info").html("Sorry, Player 1 has won.<br><br>Want to play again?<br><br><a href=\"#\" id=\"yes-link\">Yes</a> / <a href=\"#\" id=\"no-link\">No</a>");
      }
      else {
        $("#player-info").html("Winner, congratulations!<br><br>Want to play again?<br><br><a href=\"#\" id=\"yes-link\">Yes</a> / <a href=\"#\" id=\"no-link\">No</a>");
      }
    }
  }
  else {
    if(gameObject.Game.last_player == 0) { // player 1's turn
      if(isPlayer1) {
        $("#players-turn").html("Ready Player 1");  
      }
      else {
        if(gameObject.Game.player1_quit == 1) {
          if(!quitAudioPlayed) {
            quitAudioPlayed = true;
            quitAudio.play();  
          }
          
          $("#players-turn").html("Player 1 Quit");  
          $("#player-info").html("Player 1 Quit the Game");
        }
        else if(gameObject.Game.declared_impossible == 2) {
          $("#player-info").html("Waiting for Player 1's Attempt<br/>to Complete the Board");
        }
        else {
          $("#players-turn").html("Player 1's Turn");  
          $("#player-info").html("Waiting for Player 1's Move");
        }
        
      }
    }
    else { // player 2's turn
      if(isPlayer1) {
        if(gameObject.Game.player2_quit == 1) {
          $("#players-turn").html("Player 2 Quit the Game");  
          $("#player-info").html("Player 2 Quit");
          quitAudio.play();
        }
        else if(gameObject.Game.declared_impossible == 1) {
          $("#player-info").html("Waiting for Player 2's Attempt<br/>to Complete the Board");
        }
        else {
          $("#players-turn").html("Player 2's Turn");
          $("#player-info").html("Waiting for Player 2's Move");
        }
      }
      else {
        $("#players-turn").html("Ready Player 2");  
      }
    }
  }
}

CanvasState.prototype.initButtons = function() {
  if(!game.myTurn || gameObject.Game.winner > 0 || gameObject.Game.player2 == "") {
    $("#overlay").show();
    $("#overlay2").show();
    $("#turn-complete").prop('disabled', true);
    $("#impossible").prop('disabled', true);
  }
  else {
    $("#overlay").hide();
    $("#overlay2").hide();
    $("#turn-complete").prop('disabled', false);
    $("#impossible").prop('disabled', false);
  }
}

CanvasState.prototype.drawGrid = function() {
  var ctx = this.ctx;

  ctx.beginPath();
  ctx.save();

  var buffer = 0;
  for (var x = 0; x <= bw; x += cellSize) {
      ctx.moveTo(buffer + x + p, p);
      ctx.lineTo(buffer + x + p, bh + p);
  }

  for (var x = 0; x <= bh; x += cellSize) {
      ctx.moveTo(p, buffer + x + p);
      ctx.lineTo(bw + p, buffer + x + p);
  }

  ctx.strokeStyle = "#ccc";
  ctx.stroke();

  ctx.restore();
}

// Creates an object with x and y defined, set to the mouse position relative to the state's canvas
// If you wanna be super-correct this can be tricky, we have to worry about padding and borders
CanvasState.prototype.getMouse = function(e) {
  var element = this.canvas, offsetX = 0, offsetY = 0, mx, my;
  
  // Compute the total offset
  if (element.offsetParent !== undefined) {
    do {
      offsetX += element.offsetLeft;
      offsetY += element.offsetTop;
    } while ((element = element.offsetParent));
  }

  // Add padding and border style widths to offset
  // Also add the <html> offsets in case there's a position:fixed bar
  offsetX += this.stylePaddingLeft + this.styleBorderLeft + this.htmlLeft;
  offsetY += this.stylePaddingTop + this.styleBorderTop + this.htmlTop;

  mx = e.pageX - offsetX;
  my = e.pageY - offsetY;
  
  // We return a simple javascript object (a hash) with x and y defined
  return {x: mx, y: my};
}

// transforms the existing tiles based on the difference in cell size from a zoom event
CanvasState.prototype.updateTileSizes = function(oldCellSize, newCellSize) {
  var l = this.shapes.length;

  for(var i = 0; i < l; i++) {
    var xDist = Math.round(this.shapes[i].x / oldCellSize);
    var yDist = Math.round(this.shapes[i].y / oldCellSize);

    if(oldCellSize < newCellSize) {
      var t1 = (newCellSize % 50) / 20;
      var t2 = Math.floor((newCellSize % 50) / 20);

      if(t1 == t2) {
        xDist--;
        yDist--;
      }
      
    }
    else {
      if(t1 == t2) {
        xDist++;
        yDist++;
      }
    }

    var left = xDist * newCellSize;
    var top = yDist * newCellSize;

    this.shapes[i].x = left;
    this.shapes[i].y = top;
    this.shapes[i].w = newCellSize;
    this.shapes[i].h = newCellSize;
  }
}

function GameState() {
  this.totalTiles = MAX_TILES;
  this.currentTile = 0;
  this.myTurn = 0;
}

GameState.prototype.isValid = function() {
  if(gameObject.Game.declared_impossible > 0) {
    if(game.isWin()) return true;

    return false;
  }

  var startingIndex = canvasState.shapes.length - this.currentTile;
  var placedTiles = [];

  // create an array for the tiles that were moved this turn
  for(var i = startingIndex; i < canvasState.shapes.length; i++) {
    placedTiles.push(canvasState.shapes[i]);
  }

  var found = this.isValidPlacement(placedTiles);

  if(!found) return false;

  found = this.isConnectedToBoard(placedTiles);

  return found;
}

// makes sure the tiles are in a straight line and connected to each other
GameState.prototype.isValidPlacement = function(tiles) {
  var diffX = 0;
  var diffY = 0;

  // sort by x axis
  tiles.sort(function(a, b) { return a.x - b.x; });

  // test for straight line in given direction 
  for(var i = 1; i < tiles.length; i++) {
    diffX = Math.max(diffX, Math.abs(tiles[0].x - tiles[i].x));
  }

  // sort by y axis 
  tiles.sort(function(a, b) { return a.y - b.y; });

  for(var i = 1; i < tiles.length; i++) {
    diffY = Math.max(diffY, Math.abs(tiles[0].y - tiles[i].y));
  }

  var maxDistance = (tiles.length - 1) * cellSize;

  return (diffX == 0 || diffY == 0) && (diffX <= maxDistance && diffY <= maxDistance); 
}

// checks for the win condition of completing a loop
// we do this with DFS of the tiles, if we reach all tiles and return to
// the left station, then we have a closed loop
GameState.prototype.isWin = function(tile, count, seen) {
  if(tile == undefined) tile = canvasState.shapes[1];
  if(count == undefined) count = 0;
  if(seen == undefined) seen = [];

  // win conditions, we returned to the left station tile and visited every tile
  if(count == canvasState.shapes.length-1 && tile.shapeType == STATION_LEFT_TILE) {
    return true;
  }
  else if(count == canvasState.shapes.length-1) {
    return false;
  }

  seen.push(tile);

  //console.log(tile.shapeType + " " + tile.x + ", " + tile.y + " " + seen);

  var result = false;

  // move only right
  if(tile.shapeType == STATION_LEFT_TILE || tile.shapeType == STATION_RIGHT_TILE) {
    var nextTile = this.getNextTile(tile, RIGHT);

    //console.log("station tile next " + nextTile);

    if(nextTile && seen.indexOf(nextTile) == -1) {
      result = this.isWin(nextTile, count + 1, seen);  
    }
  } // move left and right or up and down depending on angle of tile
  else if(tile.shapeType == STRAIGHT_TILE) {
    if(tile.angleInRadians == 0 || tile.angleInRadians == ONE_EIGHT_DEGREES) {
      dir1 = LEFT; dir2 = RIGHT;
    }
    else {
      dir1 = UP; dir2 = DOWN;
    }

    var nextTile = this.getNextTile(tile, dir1);
    if(nextTile) {
      if(seen.indexOf(nextTile) == -1) result = this.isWin(nextTile, count + 1, seen); 
    }
    else {
      return false;
    }

    nextTile = this.getNextTile(tile, dir2);
    if(nextTile) {
      if(seen.indexOf(nextTile) == -1) result = this.isWin(nextTile, count + 1, seen); 
    }
    else {
      return false;
    }
  } // move based on curve orientation
  else {
    var dir1, dir2;
    if(tile.angleInRadians == 0) {
      dir1 = RIGHT; dir2 = DOWN;
    }
    else if(tile.angleInRadians == NINETY_DEGREES) {
      dir1 = LEFT; dir2 = DOWN;
    }
    else if(tile.angleInRadians == ONE_EIGHT_DEGREES) {
      dir1 = LEFT; dir2 = UP;
    }
    else if(tile.angleInRadians == TWO_SEVENTY_DEGREES) {
      dir1 = UP; dir2 = RIGHT;
    }

    var nextTile = this.getNextTile(tile, dir1);
    if(nextTile) {
      if(seen.indexOf(nextTile) == -1) result = this.isWin(nextTile, count + 1, seen);
    }
    else {
      return false;
    }

    nextTile = this.getNextTile(tile, dir2);
    if(nextTile) {
      if(seen.indexOf(nextTile) == -1) result = this.isWin(nextTile, count + 1, seen);
    }
    else {
      return false;
    }
  }

  return result;
}

// based on the direction, finds a tile in that direction next to the passed in tile
GameState.prototype.getNextTile = function(tile, direction) {
  var x = tile.x + 1;
  var y = tile.y + 1;

  if(direction == LEFT) x -= cellSize;
  else if(direction == RIGHT) x += cellSize;
  else if(direction == UP) y -= cellSize;
  else if(direction == DOWN) y += cellSize;

  for(var i = 0; i < canvasState.shapes.length; i++) {
    var s = canvasState.shapes[i];

    if(s.contains(x, y)) {
      if(s.shapeType == CURVE_TILE) {
        console.log(direction + " found curve here: ");
        console.dir(s);
        if(direction == LEFT 
          && (s.angleInRadians == 0 || s.angleInRadians == TWO_SEVENTY_DEGREES)) {

          return s;
        }
        else if(direction == RIGHT 
          && (s.angleInRadians == NINETY_DEGREES || s.angleInRadians == ONE_EIGHT_DEGREES)) {

          return s;
        }
        else if(direction == UP 
          && (s.angleInRadians == 0 || s.angleInRadians == NINETY_DEGREES)) {

          return s;
        }
        else if(direction == DOWN 
          && (s.angleInRadians == ONE_EIGHT_DEGREES || s.angleInRadians == TWO_SEVENTY_DEGREES)) {

          return s;
        }
      }
      else return s;
    }
  }

  return false;
}

// makes sure that the placed tiles are connected to the existing board
GameState.prototype.isConnectedToBoard = function(tiles) {
  var found = false;
  // make sure we at least one tile connected to the existing board
  for(var i = 0; i < tiles.length; i++) {
    if(this.isTileConnected(tiles[i])) {
      found = true;
      break;
    }
  }

  return found;
}

// tests all existing tiles to make sure the tile is attached to the board
GameState.prototype.isTileConnected = function(tile) {
  var found = false;
  var x = tile.x + 1;
  var y = tile.y + 1;

  for(var i = 0; i < canvasState.shapes.length; i++) {
    var s = canvasState.shapes[i];

    if(s.moveable) break;

    if(s.contains(x + cellSize, y) 
      || s.contains(x - cellSize, y)
      || s.contains(x, y + cellSize)
      || s.contains(x, y - cellSize)) {
      found = true;
      break;
    }
  }

  return found;
}

// adds a new tile to the board
GameState.prototype.handleTileAdd = function(shapeType) {
  // make sure it is this player's turn and that they have tiles left in their move
  if(game.totalTiles > 0 && game.currentTile < MAX_TURN_TILES && game.myTurn) {
    var tile = new Tile(Math.round(bw / 2), Math.round(bh / 2), cellSize, cellSize, shapeType);
    canvasState.selection = tile;
    tile.snapToGrid(canvasState.ctx, canvasState.shapes);

    canvasState.addTile(tile);

    tileDrop.play();
    
    game.currentTile++;
    game.totalTiles--;
  }
  else if(!game.myTurn) {
    alert("Sorry, but you must wait until your opponent is done playing before making your next move.");
  }
  else if(game.totalTiles == 0) {
    alert("Sorry, there are no tiles left.");
  }
  else if(game.currentTile >= MAX_TURN_TILES) {
    alert("Sorry, but you can only use between 1 and 3 tiles per turn.");
  }
}

// if the player has made a valid move, submits and saves that information and changes to the other player's turn
GameState.prototype.handleTurnComplete = function() {
  if(game.currentTile > 0) {
    if(game.isValid()) {
        var boardState = JSON.stringify(canvasState.shapes);
        var win = game.isWin() == true ? 1 : 0;

        $.post("/pages/save/" + gameObject.Game.game_key + "/" + game.currentTile + "/" + win, { GameBoard: boardState }, function(data, textStatus) {
          if(data.result == "SUCCESS") {
            gameObject = data.game;

            initBoard();
          }
          else {
            alert(data.message);
          }
        }, "json");
    }
    else {
      if(gameObject.Game.declared_impossible > 0) {
        alert("Sorry, but your current move is not valid.<br/><br/>If you cannot complete a loop, you must concede.");
      }
      else {
        alert("Sorry, but your current move is not valid. Please correct before completing your turn.");  
      }
    }
  }
  else {
    alert("Sorry, but your current move is not valid. You must move at least one tile into position.");
  }
}

// concede's the game when one player has declared the game impossible
GameState.prototype.handleYield = function() {
  bootbox.confirm({
      message: 'You are about to concede the win to your opponent. Are you sure you want to do this?',
      buttons: {
          'cancel': {
              label: 'Cancel',
              className: 'btn'
          },
          'confirm': {
              label: 'Yes',
              className: 'btn'
          }
      },
      callback: function(result) {
          if(result) {
          
          $.getJSON("/pages/concede/" + gameObject.Game.game_key, function(data) {
            if(data.result == "FAILURE") {
                alert(data.message);
            }
            else {
              gameObject = data.game;
              initBoard();
            }
          });
        }
      }
  });
}

GameState.prototype.handleDeclareImpossible = function() {
  bootbox.confirm({
      message: 'You are about to declare that the game is impossible to complete. Your opponent will be given an opportunity to complete the board. If they do, they will win.<br><br>Do you wish to continue?',
      buttons: {
          'cancel': {
              label: 'Cancel',
              className: 'btn'
          },
          'confirm': {
              label: 'Yes',
              className: 'btn'
          }
      },
      callback: function(result) {
          if(result) {
          
          $.getJSON("/pages/impossible/" + gameObject.Game.game_key, function(data) {
            if(data.result == "FAILURE") {
                alert(data.message);
            }
            else {
              gameObject = data.game;
              initBoard();
            }
          });
        }
      }
  });
}

// need to update shape sizes
GameState.prototype.zoomIn = function() {

}

GameState.prototype.zoomOut = function() {

}

function initBoard() {
  game.totalTiles = MAX_TILES - gameObject.Game.tiles_used;
  game.currentTile = 0;
  game.myTurn = (gameObject.Game.last_player == 0 && isPlayer1) || (gameObject.Game.last_player == 1 && !isPlayer1);

  if(gameObject.Game.board) {
    var object = JSON.parse(gameObject.Game.board);
    canvasState.shapes = [];
    for(var i = 0; i < object.length; i++) {
      var t = object[i];
      var s = new Tile(t.x, t.y, t.w, t.h, t.shapeType, false, t.angleInRadians);

      if(t.shapeType == STATION_LEFT_TILE) leftStation = s;
      else if(t.shapeType == STATION_RIGHT_TILE) rightStation = s;

      canvasState.addTile(s);
    }
  }

  // game has been declared impossible, go into impossible solve state
  if(gameObject.Game.declared_impossible > 0 && gameObject.Game.winner == 0) {
    if(!impossibleAudioPlayed) {
      impossibleAudioPlayed = true;
      impossibleMusic.play();  
    }
    
    if(game.myTurn) {
      MAX_TURN_TILES = 16;  

      $("#impossible").hide();
      $("#yield-win").show();

      alert("Your opponent has declared that this board is impossible to complete.<br/><br/>Given the tiles left, if you can complete a loop, you win. If not, your opponent wins.");
    }
  }

  if(gameObject.Game.winner > 0) {
    if(!winAudioPlayed) {
      winAudioPlayed = true;
      winMusic.play();  
    }
  }

  canvasState.selection = false;
  canvasState.valid = false;

  if(!game.myTurn && gameObject.Game.winner == 0) {
    turnPolling = setTimeout(updateGame, 5000);
  }
  else {
    clearInterval(turnPolling);
  }
}

// polls the game state when waiting for your turn
function updateGame() {
  if(!inProcess) {
    inProcess = true;

    $.getJSON("/pages/update/" + gameObject.Game.game_key, function(response) {
      // console.log(response);
      if(response.result == "SUCCESS") {
        inProcess = false;

        var prevTurn = game.myTurn;

        gameObject = response.game;
        initBoard();

        // turn change, play turn change sound
        if(prevTurn != game.myTurn && gameObject.Game.winner == 0) {
          turnChange.play();
        }
      }
      else {
        inProcess = false;
      }
    });
  }
}

function initListeners() {
  if(listener) return;

  listener = true;

  $("#new-straight").click(function(e) {
    e.preventDefault();
    game.handleTileAdd(STRAIGHT_TILE);
  });

  $("#new-curve").click(function(e) {
    e.preventDefault();
    game.handleTileAdd(CURVE_TILE);
  });

  $("#turn-complete").click(function(e) {
    e.preventDefault();

    game.handleTurnComplete();
  });

  $("#impossible").click(function(e) {
    e.preventDefault();

    game.handleDeclareImpossible();
  });

  $("#yield-win").click(function(e) {
    e.preventDefault();

    game.handleYield();
  });

  $(document).on("click", "#yes-link", function(e) {
    e.preventDefault();

    playAgain();
  });

  $(document).on("click", "#no-link", function(e) {
    e.preventDefault();

    performQuit();
  });
}

function initGame() {
  if(game == null) {
    game = new GameState();
    canvasState = new CanvasState(document.getElementById('canvas'));

    // add the left and right stations
    var cellLocation = ((bw / cellSize) / 2) - 1;
    var x = cellLocation * cellSize;
    var y = cellLocation * cellSize;

    //console.log("dafsd");

    leftStation = new Tile(x, y, cellSize, cellSize, STATION_LEFT_TILE, false);
    canvasState.addTile(leftStation);

    cellLocation = ((bw / cellSize) / 2);
    x = cellLocation * cellSize;
    y = (cellLocation - 1) * cellSize;

    rightStation = new Tile(x, y, cellSize, cellSize, STATION_RIGHT_TILE, false);
    canvasState.addTile(rightStation);

    canvasState.valid = false;

    initListeners();
  }
  

  if(!gameObject.Game.board && gameObject.Game.player2 != "") {
    gameStart.play();
  }

  initBoard();
}
