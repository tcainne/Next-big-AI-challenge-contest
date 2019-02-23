# Next big AI challenge contest
All you need to know to participate and win !

# Why this contest ?

# Try it with a ready to use PHP script

This script is not oriented-object for simplicity reasons.

You need a command line PHP binaries 

Download Runner.php and DonkeyAI.php here :

Get your API Key here :https://github.com/tcainne/Next-big-AI-challenge-contest/blob/master/README.md#how-to-get-your-api-key

Open with an editor Runner.php

Search $apikey='XXXXXXXXXX', and replace XXXXXXXXXX with your API Key.

Simply run : "/usr/bin/php ./Runner.php"

Runner.php have some options. To use an option "/usr/bin/php ./Runner.php --debug=1 --maxaitime=20"


Options list :

- debug (Default: 0 . Debug information level from 0 to 3)

- ai (Default: donkey .Choose an AI. Right now only "donkey" is available. A more intelligent one will come soon.)

- debug (Default: 0 . Debug information level from 0 to 3)

- killme (Default: 0 . If set to 1, you will forgive your current game.)

- maxdepth (Default: 2 . AI Donkey is a recursive function for computing conbinations.  maxdepth is so the recursive depth of combinations.)

- maxaitime ( Default : 25 . In Lords of guilds, during a fight/game you only have 30 seconds beetween each actions, so AI process is stopped over "maxaitime" seconds of processing.




# How to get your API key
Install the Lords of rings app on you device : http://www.lordsofguilds.com/

Create a player.

Follow tuto.

Your API key is here (Left top corner of the mains creen) ->



# Two APIs ?

# The game API
Game API documentation is available here : https://documenter.getpostman.com/view/5715617/RzfmE6b9

The game API is used to managed your account, decks, cards, player, etc ... This is all your need to create your own user interface in any langage you want !

Well, excepting user creation ;) Right now, to create a user/account you need a android or apple device which will give you an API key : Go here for more explanations https://github.com/tcainne/Next-big-AI-challenge-contest/blob/master/README.md#how-to-get-your-api-key

# The fight API

For performance issues, "Fights" are handled by in-memory daemons on backend servers.

After creating a fight/game with the game API, you will have to connect to a TCP-socket. Ip,port and password are provided trough the game API. 

So this API is not a true Rest one. In fact, it is more a list of "commands", than a standard API.

Here is the documentation : 

I choose Postman too to publish it to the world, but forget "GET" and other API stuffs.

