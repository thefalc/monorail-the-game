# Monorail as seen on The Genius

Monorail is a two player game from a Korean reality television show called The Genius. The game is played as follows.

Two tiles that form a train station are laid out in front of the players. Players take turns where each player can lay down between one and three tiles per turn from a set of 16 total tiles. There are only two different types of tiles, a curve and a straight line. The tiles that are laid down by a player must be in a straight line and must connect to the existing pieces already placed to form the board.

The first player to complete a single loop wins the game. There’s a second win condition as well.

If after one player completes their turn the other player determines that the game cannot be completed with the remaining tiles, they can declare that the game is impossible to complete. Once they do that, the other player has the opportunity to use any and all remaining tiles to complete a loop. If they complete the loop, they win, if they cannot, the player that declared the game impossible wins.

You can read about the project [here](https://thefalc.com/2016/02/monorail-as-seen-on-the-genius/).

![Monorail](/assets/monorail_the_game.jpeg)

## Technical details

The project runs on the LAMP stack, the backend uses the [CakePHP](http://www.cakephp.org) framework. The frontend is a combination of vanilla Javascript and jQuery. The game itself are painted onto an HTML canvas object.

**Code structure**
* The backend code keeps track of players, live chat history, and game state. Take a look at the [PagesController](https://github.com/thefalc/monorail-the-game/blob/main/app/Controller/PagesController.php).
* The frontend pages for laying out the game are can be found in [app/View/Pages](https://github.com/thefalc/monorail-the-game/tree/main/app/View/Pages).
* The game logic is available in [monorail.js](https://github.com/thefalc/monorail-the-game/blob/main/app/webroot/js/monorail.js). The game win condition is checked via a recursive function [isWin](https://github.com/thefalc/monorail-the-game/blob/da6985460c6a1cf3c5ae62692e37d13a4233bb9a/app/webroot/js/monorail.js#L672).
* The database structure is defined in [monorail.sql](https://github.com/thefalc/monorail-the-game/blob/main/monorail.sql).
