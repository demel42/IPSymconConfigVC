# IPSymconConfigVC

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.0+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Module-Version](https://img.shields.io/badge/Modul_Version-1.8-blue.svg)
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

Es werden verschiedene Dateien und Daten aus dem IP-Symcon-Verzeichnis in Git gesichert und versioniert.

Die Daten sind
#### Globale Dateien
Die Datei _setting.json_ wird gesichert sowie die Datei _php.ini_.

#### Scripte
die Scripte aus dem Scriptverzeichnis werden, falls geändert, übernommen. Neue Dateien werden hinzugefügt, zwischenzeitlich gelöschte Dateien werden auch im Repository gelöscht.

#### Module
Die installierten Module werden untersucht. Es wird pro Modul eine Datei _<Modul-Verzeichnis>.json_ angelegt, die die Repository-URL (_url_), den Branch (_branch_), die den altuellen Versionsstand (_commitID_) und den Zeitpunkt der letzten Modifikation einer Datei (_mtime_) aus dem Modul-Verzeichnis enthält.
Die Datei ändert sich somit nur, wenn sich auch in dem Modul etwas geändet hat.
Anhand dieser Daten kann auch eine bestimmte Version des Moduls wieder hergestellt werden; hierzu geht man wie folgt vor:
- Modul in der Modulverwaltung löschen (іnicht die installierte Instanz des Modules!)
- Modul neu anlegen, jedoch nicht (nur) die Url angeben sondern so: _**url**/commit/**commitID**_ (z.B. _https://github.com/demel42/IPSymconConfigVC/commit/3434195d8ea0b745d92e707a186c0bff03c5884d_). Damit ist man wieder zurück auf dem früheren Stand.
Module, die nicht unter Git-Kontrolle stehen, werden als Zip-Archiv gesichert (nur wenn _with_zip_ = _true_ - siehe unten).

Wenn man wieder auf den aktuellen Stand zurückgehen möchte, muss man das analog durchführen: löschen und dann wieder die Original-URL angeben und ggfs den passenden Branch auswählen.

#### Skins
Analog zu den Modulen werden die unter _webfront/skins_ installierten Skins gesichert.
Skins, die nicht unter Git-Kontrolle stehen, werden als Zip-Archiv gesichert (nur wenn _with_zip_ = _true_ - siehe unten).

#### IPS-Objekte & co
Da sich der Inhat von _settings.json_ sehr dynamisch ändert und ein vorher/nachher-Vergleich sehr unübersichtlich ist, wird noch folgendes gemacht:

es wird per IPS_GetSnapshot()_ der aktuelle Zustand geholt. Hieraus wird exportiert

- _options.json_ (_Spezialschalter_) wird im Verzeichnis _settings_ gesichert.

- Objekte<br>
alle Objekte werden aus dem Snapshot heraus (also auf aktuellem Stand) gesichert, dabei wird pro Objekt eine Datei im Unterverzeichnis _settings/objects_ angelegt. Diese Datei enthält die json-Struktur des jeweiligen Objekts. Um nur relevante Änderungen zu sehen werden eventuelle Zeitstempel oder die Werte der Variablen entfernt. Da die Datei nur geschrieben wird, wenn sich etwas geändert hat.

- Profile<br>
die Profile werden als Ganzes in der Datei _profile.json_ im Verzeichnis _settings_ und noch einmal jedes Profil einzeln in _settings/profiles_ unter dem Profilnamen. <br>
Hinweis: es werden einige spezielle Zeichen im Dateinamen durch ein Unterstrich ersetzt.

Auch diese Dateien werden grundsätzlich nur geschrieben, wenn sich etwas geändert hat, damit ist das leicht am Zeitstempel der Datei zu erkennen.

#### Media
Dateien im Verzeichnis _media_ werden gesichert.

#### Webfront
Verzeichnisse unterhalb von _webfront/user_ werden optional als Zip-Archiv gesichert.

#### Datenbank
Die Datenbank (_db_) wird optional gesichert, Aggregationsdaten werden nicht gesichert.

#### README.md
In dieser Datei wird ein Protokoll der Änderungen des letzten Abgleichs dargestellt. Man sieht als sehr schnell, an welcher Stelle der Konfiguration seit dem letzten Lauf sich etwas geändert hat.
Die Änderungen selber kann man dann auch leicht im Git darstellen.
<br><br>
Die Dauer eines Abgleich ist naturgemäß schwierig allgemeingültig darzustellen. Ein Anhaltswert: auf einem Raspberry 3B+ auf SD-Karte mit ca. 30 Scripten, 2000 Variablen und 20 Modulen dauert der initiale Abgleich ohne Erstellen von Zip-Archiven ca. 20 Sekunden, jeder weiterer Abgleich ca. 2-3 Sekunden. Die Zeit für das Erstellen von Zip-Archiven (_webfront/user_) ist sehr individuell, dürfte aber im Bereich einiger Minuten liegen.

Da alle Änderungen von IPS bei Abgleich in das lokale Repository übertragen und direkt an das zentrale Repository übertragen werden, ist dieses Verzeichnis nur von temporärem Interesse und kann jderzeit neu erstellt werden.

Wichtig: dieses Modul ersetzt auf keinen Fall eine regelmässige Datensicherung! Es ist gedacht als Unterstützung bei einer Suche danach, wann eventuell Änderungen durchgeführt wurden und wenn ja, welche.

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

##### lokaler Git-Server auf raspbian und ubuntu

siehe auch `https://git-scm.com/book/de/v1/Git-auf-dem-Server-Einrichten-des-Servers` und `https://www.linux.com/learn/how-run-your-own-git-server`.

Der User _git_ ist Platzhalter, ebenso _git-server_ oder _ipsymcon.git_ bzw _ipsymcon_. Die Namen bzw Pfad können natürlich nach Bedarf angepasst werden.

Bei einem Raspberry sollte man vielleicht den Git-Server-Repository also auch das geclone Git-Repository nicht auf der internen SD-Karte anlegen.

**auf dem Git-Server**
```
sudo -i
adduser git
<passwort eingeben und merken>
mkdir -p ~git/repositories/ipsymcon.git
cd ~git/repositories/ipsymcon.git
git init --bare
chown -R git:users ~git/repositories
```

**auf dem IPS-Server**<br>
Hintergrund: damit es richtig funktioniert muss mindestens eine Datei im Git-Repository vorhanden sein.

```
cd /tmp
git clone ssh://git@git-server/home/git/repositories/ipsymcon.git
cd ipsymcon
touch README.md
git add README.md
git commit -m "initial revision"
git push
cd /tmp
/bin/rm -rf /tmp/ipsymcon
```

#### Git-Server auf Synology DiskStation

**auf der Synology DiskStation**

Paket 'Git Server' installieren

Login als User _admin_
```
cd /volume1/git
mkdir ipsymcon.git
cd ipsymcon.git
git init --bare
```

**auf dem IPS-Server**

Der Git-User kann aber muss nicht _admin_ sein. Der ssh-Port ist typischerweise nicht _22_, sondern z.B. _5002_.

```
cd /tmp
git clone ssh://admin@git-server:5002/volume1/git/ipsymcon.git
cd ipsymcon
touch README.md
git add README.md
git commit -m "initial revision"
git push
cd /tmp
/bin/rm -rf /tmp/ipsymcon
```

#### Git-Repository auf öffentlichen Git-Server (wie _GitHub_)

Wichtig: das Repository muss auf jeden Fall als **_privat_** eingerichtet werden! Ein lokaler Git-Server ist auf jeden Fall zu bevorzugen. Immerhin sind in diesem Git-Repository alle internen Informationen, Zugangsdaten u.s.w. enthalten.

Für die Ansicht des Git-Repositories (ähnlich der Web-Oberfläche von GitHub) stehen verschiedene Programme zur Verfügung.

Bei der Anlage des Repository sollte die Option gewählt werden, direkt ein leeres _README.md_ anzulegen; alternativ muss eine leere Datei wie oben beschrieben angelegt werden.

#### ssh-Keys einrichten für lokale ssh-basierte Repositories

**auf dem IPS-Server**

Sowohl als User _pi_ als auch als User _root_ (IPS läuft ja als _root).

- falls keine Datei _~/.ssh/authorized_keys_ vorhanden ist
```
ssh-keygen -t rsa -b 2048
```
Die Fragen alle mit <return> beantworten.

- Verteilen des ssh-Key auf den Git-Server:
```
ssh-copy-id git@git-server
```
Bei nicht-Standard ssh-Port
```
ssh-copy-id -p 5002 git@git-server
```

Als User den oben gewählten Git-User verwenden.


#### Einrichtung auf dem IPS-Server
```
mkdir <Verzeichnis für lokales respoitory>
```

### Einrichtung in IP-Symcon

Der Abgleich wird nicht automatisch aufgerufen. Hierzu muss man ein kleines Script erstellen:

```
<?

CVC_CallAdjustment(4711 /*[System\Configuration Version-Control*/, true, true);
```
und dieses dann im gewünschten Zeitmuster aufrufen.<br>
Der Wert _true_ besagt, das, wenn in der Konfiguration so eingestellt, die Zip-Archive erstellt werden, _false_ bedeutet, das das nicht gemacht wird.

Mit diesem Script werden (bei stündlichem Aufruf) die Zip-Archvie nur um 0 Uhr abgeglichen, sonst nur die anderen Dateien/Objekte und die Vollständige Überprüfung nur Sonntag um 0 Uhr.

```
<?

// Zip-Archive nur um Mitternacht
$with_zip = date("H", time()) == 0 ? true : false;
// vollständige Dateiüberprüfung nur am Sonntag um Mitternacht
$full_file_cmp = date("w", time()) == 0 && date("H", time()) == 0 ? true : false;
CVC_CallAdjustment(4711 /*[System\Configuration Version-Control*/, $with_zip, $full_file_cmp);

```
Bei Bedarf kann man natürlich jederzeit einen Abgleich manuell über die Schaltflächen _Abgleich_ durchführen.

## 4. Funktionsreferenz

### zentrale Funktion

`boolean CVC_CloneRepository(integer $InstanzID)`<br>
legt ein frischen Clone des Repositories an. Dabei wird, wenn erforderlich, ein vorhænderen Clone gelöscht. Das angegebene lokale Verzeichnis muss vorhanden sein.<br>

`boolean CVC_PerformAdjustment(integer $InstanzID, boolean $with_zip, boolean $full_file_cmp)`<br>
führt einen Abgleich durch. <br>
Durch _with_zip_ kann bei Aufruf gesteuert werden, ob überhaupt Zip-Archive gebildet werden oder nicht (wirkt nur, _webfront/user als Zip_ gesetzt ist).
Durch _full_file_cmp_ regelt, wie Dateien auf Gleichheit verglichen werden. Bei dem einfachen Vergleich wird die Gräße und der Zeitpunkt der letzten Änderung verwendet: bei einer vollständigen Überprüfung wird der Dateiinhalt selbst verglichen. Das bietet eine erhöhte Sicherheit, alle Änderungen zu berücksichtigen, ist aber sicherlich nur ab und an erforderlich.

## 5. Konfiguration:

### Variablen

| Eigenschaft                          | Typ      | Standardwert    | Beschreibung |
| :----------------------------------: | :-----:  | :-------------: | :----------------------------------------------------------------------------------------------------------: |
| Git-Repository                       | string   |                 | Pfad zu Git-Repository |
| Benutzer                             | string   |                 | Benutzer des Git-Repository (für https und ssh) |
| Passwort                             | string   |                 | Passwort (nur für https) |
| Port                                 | integer  | 22              | SSH-Port (nur für ssh) |
| ... user.name                        | string   | IP-Symcon       | Angabe für 'git config --global user.name' |
| ... user.email                       | string   |                 | Angabe für 'git config --global email.name'. Angabe ist zwingend und muss eine korrekte Mail-Adresse sein |
| lokales Verzeichnis                  | string   |                 | lokales Verzeichnis indem der Clone des Git-Repository abgelegt wird |
| zusätzliche Verzeichnisse            | string   |                 | Liste von zusätzlich zu sichernden Verzeichnissen, relativ zum Symcon-Verzeichis (_/var/lib/symcon_) |
| Webfront/user als Zip-Archiv sichern | boolean  | false           | Sichern von Webfront/user als Zip-Archiv. Achtung: Größe beachten! |
| Datenbank sichern                    | boolean  | false           | Daten in Verzeichnis 'db' sichern |

#### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------: | :-------------------------------------------------: |
| Vollständiger Abgleich       | führt einen vollständigen Abgleich durch (incl. Zip-Archive erstellen und kompletten Vergleich der Dateien auf Gleichheit |
| Schneller Abgleich           | führt einen schnellen Abgleich durch (ohne die zuvor Punkte) |
| Repository einrichten        | erzeugt einen aktuelle Clone des Repositories |

### Variablenprofile

Es werden folgende Variableprofile angelegt:
* Integer<br>
  - ConfigVC.Duration

## 6. Anhang

GUIDs

- Modul: `{EA906449-5DDA-4D99-9110-276BB31B6FFC}`
- Instanzen:
  - ConfigVC: `{396EA137-2E5F-413A-A996-D662158EA481}`

## 7. Versions-Historie

- 1.9 @ 24.03.2019 11:16<br>
  - Kommando-Aufrufe "Windows-tauglich" gemacht
  - komplette Fehlermeldung der aufgerufenen git-Kommandos bei Fehlschlag

- 1.8 @ 21.03.2019 20:37<br>
  - Anpassung an IPS 5

- 1.7 @ 21.12.2018 13:10<br>
  - Standard-Konstanten verwenden

- 1.6 @ 08.11.2018 17:15<br>
  - Möglichkeit, zusätzlich zu sichernde Verzeichnisse anzugeben

- 1.5 @ 17.09.2018 10:17<br>
  - nun 2 Schaltflächen für den Abgleich (_Vollständig_ und _Schnell_)
  - Vergleich von Dateien nun per _sha1_file()_ statt Vergleich der kompletten Datei im Speicher (Problem bei großen Dateien)
  - Vergleich des Dateiinhalts nur noch optional (Performance)

- 1.4 @ 15.09.2018 18:50<br>
  - Unterstützung von _http_ für lokale Repositories
  - Angabe von user.name und user.email zum korrekten _git config_
  - nach Betätigung der Schaltflächen wird ein Popup angezeigt mit Angabe von Erfolg oder Fehlschlag

- 1.3 @ 13.09.2018 09:08<br>
  - optional Verzeichnis _db_ sichern
  - Verzeichnis _media_ sichern
  - Fix für den Umgang mit leeren Dateien

- 1.2 @ 12.09.2018 08:05<br>
  - Modules und Skins, die nicht unter Git-Kontrolle stehen werden als Zip-Archiv gesichert
  - Code aufgeräumt

- 1.1 @ 10.09.2018 10:14<br>
  - Commit-ID der Module in den <modules>.json-Dateien gesichert, Dokumentation ergänzt
  - keine Zip-Archive mehr für Module
  - optional Zip-Archive für Verzeichnisse unterhalb von _webfront/user_
  - Sicherung der Informationen zu _webfront/skins_ (analog zu Modulen)
  - Sicherung von php.ini
  - Umgang mit nicht-uft8-Daten bei _IPS_Snapshot()_

- 1.0 @ 01.09.2018 10:12<br>
  Initiale Version
