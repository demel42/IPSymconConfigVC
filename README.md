# IPSymconConfigVC

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.0-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-1.0-blue.svg)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)
[![StyleCI](https://github.styleci.io/repos/126683101/shield?branch=master)](https://github.styleci.io/repos/146979798)

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Installation](#3-installation)
4. [Funktionsreferenz](#4-funktionsreferenz)
5. [Konfiguration](#5-konfiguration)
6. [Anhang](#6-anhang)
7. [Versions-Historie](#7-versions-historie)

## 1. Funktionsumfang

## 2. Voraussetzungen

 - IP-Symcon ab Version 5

## 3. Installation

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconConfigVC.git`

und mit _OK_ bestätigen.

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### Einrichtung in IPS

In IP-Symcon nun _Instanz hinzufügen_ (_CTRL+1_) auswählen unter der Kategorie, unter der man die Instanz hinzufügen will, und Hersteller _(sonstiges)_ und als Gerät _Configuration Version-Control_ auswählen.

### Einrichtung des Git-Repositories

    `https://git-scm.com/book/de/v1/Git-auf-dem-Server-Einrichten-des-Servers`
    `https://www.linux.com/learn/how-run-your-own-git-server`

##### lokaler git-server auf raspbian und ubuntu

# auf eigenem Git-Server einrichten

```
sudo -i
adduser git
<passwort eingeben und merken>
mkdir -p ~git/repositories/ipsymcon.git
cd ~git/repositories/ipsymcon.git
git init --bare
chown -R git:users ~git/repositories
```

	# auf dem IPS-Server
	cd /tmp
	git clone ssh://git@<git-server>/home/git/repositories/ipsymcon.git
	cd ipsymcon
	touch README.md
	git add README.md
	git commit -m "initial revision"
	git push
	cd /tmp
	/bin/rm -rf /tmp/ipsymcon

	ODER

	# lokaler git-server auf Synology

	# auf der synology
	Paket 'Git Server' installieren
	<Login als 'admin'>
	cd /volume1/git
	mkdir ipsymcon.git
	git init --bare


	# auf dem IPS-Server
	cd /tmp
	git clone ssh://admin@<git-server>:5002/home/git/repositories/ipsymcon.git
	cd ipsymcon
	touch README.md
	git add README.md
	git commit -m "initial revision"
	git push
	cd /tmp
	/bin/rm -rf /tmp/ipsymcon

	ODER

	privates Repository auf github direkt mit README.md anlegen

	# ssh-keys einrichten für lokale ssh-repositories

	auf dem IPS-Server
	
	als user 'pi'
	< falls kein ~/.ssh/authorized_keys vorhanden ist >
    ssh-keygen -t rsa -b 2048
	< Fragen alle mit <return> beantworten >

    ssh-copy-id git@<git-server>

	bzw für Synology statt user 'git' den user 'admin' verwenden

	das gleiche wiederholen für user 'root' (symcon läuft als root)

	sudo -i

    ssh-copy-id git@<git-server>

	# auf dem IPS-Server

    mkdir <Verzeichnis für lokales respoitory>
	-> auf einem Raspberry statt auf der SD-Karte besser auf einem USB-Stick


## 4. Funktionsreferenz

### zentrale Funktion

`boolean CVC_Perform(integer $InstanzID)`<br>

## 5. Konfiguration:

### Variablen

| Eigenschaft                     | Typ      | Standardwert | Beschreibung |
| :-----------------------------: | :-----:  | :----------: | :----------------------------------------------------------------------------------------------------------: |
| Git-Repository                  | string   |              | Pfad zu Git-Repository |
| Benutzer                        | string   |              | Benutzer des Git-Repository (für https und ssh) |
| Passwort                        | string   |              | Passwort (nur für https) |
| Port                            | string   |              | SSH-Port (nur für ssh) |
| lokales Verzeichnis             | string   |              | lokales Verzeichnis indem der Clone des Git-Repository abgelegt wird |

## 6. Anhang

GUIDs

- Modul: `{EA906449-5DDA-4D99-9110-276BB31B6FFC}`
- Instanzen:
  - ConfigVC: `{396EA137-2E5F-413A-A996-D662158EA481}`

## 7. Versions-Historie

- 1.0 @ 01.09.2018<br>
  Initiale Version
