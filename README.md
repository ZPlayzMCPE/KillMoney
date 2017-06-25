![KillMoney](http://image.ibb.co/eNQEJQ/IMG_0048.png)

![GIF](http://www.gifmania.co.uk/Objects-Animated-Gifs/Animated-Money/Gold-Coins/Dolar-Symbol-Gold-Coin-80394.gif)

With this plugin you can give your players the opportunity to earn money by killing other players. This repository is not a *fork* of the original **KillMoney**, I only noticed that many people were waiting for *@Topic* to update the plugin by providing support for the new PocketMine-MP **API** 3.0.0, so I decided to make the request of many people.

Some features that come with this plugin:
- Minimum amount of money that the victim must have for the killer to obtain money from the victim
- Customizable amount of money that the killer earns and loses the victim
- Customizable messages in the config.yml with their respective descriptions (supports tags)
- Quick view of the settings and messages defined in the config.yml when using /killmoney (only OPs) (see permissions)

**This plugin depends on EconomyAPI** [Download it here](https://poggit.pmmp.io/p/EconomyAPI/5.7)**.**
**Setting up config.yml is required.**

# Commands

| Command  | Usage | Description |
| ------------- | ------------- | ------------- |
| `/killmoney`  | `/killmoney`  | Shows information about this plugin |

**The main command is useful for OPs.**

# Permissions

```yaml
 killmoney.command:
  default: op
 killmoney.killer.receive.money:
  default: false
 killmoney.victim.lose.money:
  default: false
  ```
  
  | Default | Description |
  | ------- | ----------- |
  | ```false``` | Nobody including OPs can use the command |
  | ``` true ``` | Everyone can use the command |
  | ``` op ``` | Only OPs can use the command |
  
  The *default* can be overridden by using a permission handler.
  
# Download

## Client

If you are a client of a MCPE server hosting service, **KillMoney** may be one more plugin in the *quick download* list, if not, you can suggest them to add it so you can use it on your server, in exchange for a small commission, if so.

Otherwise or if you were unable to follow download method **1**, you can check if your hosting service gives you FTP access to the server files; if so please go to *Releases* and preferably look for the latest available release to download the plugin in *.phar* format, and finally upload it in the *plugins/* folder.

## Developer

### Step 1. Clone this repository

Via ```git``` in the terminal (Shell access required)

```sh
git --recursive clone https://github.com/kenygamer/KillMoney
```

### Step 2. Compile it to get a single PHAR file

Compile the files obtained after performing the `git clone`. You can use the *sunnyct's* console application [View phar compiler](https://github.com/sunnyct/phar-compiler) that will allow you to complete this step.

### Step 3. Upload PHAR file and start the server

Upload the compressed file to the *plugins/* folder and restart the server. The plugin should be enabled and run without problems. If so, you can submit an issue and I'll try to fix it as soon as possible.

### Version tests
- [ ] Version 1.0.0
- [ ] Version 1.0.1
- [X] Version 1.1.0
