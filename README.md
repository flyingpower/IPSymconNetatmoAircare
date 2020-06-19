# IPSymconNetatmoAircare

[![IPS-Version](https://img.shields.io/badge/Symcon_Version-5.3+-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![License](https://img.shields.io/badge/License-CC%20BY--NC--SA%204.0-green.svg)](https://creativecommons.org/licenses/by-nc-sa/4.0/)

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

Anschluss der Geräte, die von Netatmo unter dem Beriff _Aircare_ zusammengefasst sind:
- Raumluftsensor

Je nach Produktyp umfasst das Modul folgende Funktionen:
- Abruf des Status
- Verwaltung der identifizierten Personen sowie Steuerung (_kommt_, _geht_)

## 2. Voraussetzungen

 - IP-Symcon ab Version 5.3
 - ein Netatmo Aircare-Modul
 - den "normalen" Benutzer-Account, der bei der Anmeldung der Geräte bei Netatmo erzeugt wird (https://my.netatmo.com)
 - IP-Symcon Connect<br>
   **oder**<br>
 - einen Account sowie eine "App" bei Netatmo Connect, um die Werte abrufen zu können (https://dev.netatmo.com)<br>
   Achtung: diese App ist nur für den Zugriff auf Netatmo-Aircare-Produkte gedacht; das Modul benutzt die Scopes _read_presence access_presence read_camera write_camera access_camera read_smokedetector_.<br>
   Eine gleichzeitige Benutzung der gleichen Netatmo-App für andere Bereiche (z.B. Weather) stört sich gegenseitig.<br>
   Die Angabe des WebHook in der App-Definition ist nicht erforderlich, das führt das IO-Modul selbst durch.

## 3. Installation

### a. Laden des Moduls

**Installieren über den Module-Store**

Die Webconsole von IP-Symcon mit http://<IP-Symcon IP>:3777/console/ öffnen.

Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

Im Suchfeld nun NetatmoAircare eingeben, das Modul auswählen und auf Installieren drücken.

**Installieren über die Modules-Instanz**

Die Konsole von IP-Symcon öffnen. Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

In der _Modules_ Instanz rechts oben auf den Button __*Hinzufügen*__ drücken.

In dem sich öffnenden Fenster folgende URL hinzufügen:

`https://github.com/demel42/IPSymconNetatmoAircare.git`

und mit _OK_ bestätigen. Ggfs. auf anderen Branch wechseln (Modul-Eintrag editieren, _Zweig_ auswählen).

Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_

### b. Einrichtung in IPS

#### NetatmoAircareIO

In IP-Symcon nun unterhalb von _I/O Instanzen_ die Funktion _Instanz hinzufügen_ (_CTRL+1_) auswählen, als Hersteller _Netatmo_ und als Gerät _NetatmoAircare I/O_ auswählen.

In dem Konfigurationsformular nun den gewünschten Zugang wählen, entweder als Nutzer über IP-Symcon Connect oder als Entwickler mit eigenem Entwicklerschlüssel.

**Zugriff mit Netatmo-Benutzerdaten über IP-Symcon Connect**

Hierzu _bei Netatmo anmelden_ auswählen. Es öffnet sich ein Browserfenster mit der Anmeldeseite von Netatmo; hier bitte anmelden. Dann erscheint ein weiteres Fenster

![OAUTH1](docs/netatmo_login_1.png?raw=true "oauth 1")

Hier bitte den Zugriff des _IP-Symcon Netatmo Connector_ akzeptieren; es erscheint 

![OAUTH1](docs/netatmo_login_2.png?raw=true "oauth 2")

Das Browserfenster schliessen.

Anmerkung: auch wenn hier alle möglichen Netamo-Produkte aufgelistet sind, bezieht sich das Login nur auf die Produkte dieses Moduls.

**Zugriff als Entwickler mit eigenem Entwicklerschlüssel**

In dem Konfigurationsdialog die Netatmo-Zugangsdaten eintragen.

#### NetatmoAircareConfig

Dann unter _Konfigurator Instanzen_ analog den Konfigurator _NetatmoAircare Konfigurator_ hinzufügen.

Hier werden alle Aircare-Produkte, die mit dem, in der I/O-Instanz angegebenen, Netatmo-Konto verknüpft sind, angeboten; aus denen wählt man ein Produkt aus.

Mit den Schaltflächen _Erstellen_ bzw. _Alle erstellen_ werden das/die gewählte Produkt anlegt.

Der Aufruf des Konfigurators kann jederzeit wiederholt werden.

Die Produkte werden aufgrund der _Produkt-ID_ identifiziert.

Zu den Geräte-Instanzen werden im Rahmen der Konfiguration Produkttyp-abhängig Variablen angelegt. Zusätzlich kann man in dem Modultyp-spezifischen Konfigurationsdialog weitere Variablen aktivieren.

Die Instanzen können dann in gewohnter Weise im Objektbaum frei positioniert werden.

## 4. Funktionsreferenz

### NetatmoAircareIO

`NetatmoAircare_UpdateData(int $InstanzID)`
ruft die Daten der Netatmo-Aircare-Produkte ab. Wird automatisch zyklisch durch die Instanz durchgeführt im Abstand wie in der Konfiguration angegeben.

## 5. Konfiguration

### NetatmoAircareIO

#### Properties

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :------------------------ | :------  | :----------- | :----------- |
| Verbindungstyp            | integer  | 0            | _Netatmo über IP-Symcon Connect_ oder _Netatmo Entwickler-Schlüssel_ |
|                           |          |              | |
| Netatmo-Zugangsdaten      | string   |              | Benutzername und Passwort von https://my.netatmo.com sowie Client-ID und -Secret von https://dev.netatmo.com |
|                           |          |              | |
| Aktualisiere Daten ...    | integer  | 5            | Aktualisierungsintervall, Angabe in Minuten |

#### Schaltflächen

| Bezeichnung                  | Beschreibung |
| :--------------------------- | :----------- |
| bei Netatmo anmelden         | durch Anmeldung bei Netatmo via IP-Symcon Connect |
| Aktualisiere Daten           | führt eine sofortige Aktualisierung durch |

### NetatmoAircareConfig

#### Properties

| Eigenschaft               | Typ      | Standardwert | Beschreibung |
| :------------------------ | :------  | :----------- | :----------- |
| Kategorie                 | integer  | 0            | Kategorie im Objektbaum, unter dem die Instanzen angelegt werden |
| Produkte                  | list     |              | Liste der verfügbaren Produkte |

### NetatmoAircareSensor

#### Properties

werden vom Konfigurator beim Anlegen der Instanz gesetzt.

| Eigenschaft              | Typ      | Standardwert | Beschreibung |
| :----------------------- | :--------| :----------- | :----------- |
| Produkt-ID               | string   |              | ID des Produktes |
|                          |          |              | |
| letzte Kommunikation     | boolean  | Nein         | letzte Kommunikation mit dem Netatmo-Server |
| Stärke des Wifi-Signals  | boolean  | Nein         | Ausgabe des Signal in den Abstufungen: _schlecht_, _mittel_, _gut_, _hoch_|

### Variablenprofile

Es werden folgende Variablenprofile angelegt:
* Boolean<br>

* Integer<br>

## 6. Anhang

GUIDs
- Modul: `{FEE67CA6-D938-284B-181D-20496B7411C2}`
- Instanzen:
  - NetatmoAircareIO: `{070C93FD-9D19-D670-2C73-20104B87F034}`
  - NetatmoAircareConfig: `{F031A9F9-D196-4852-D287-E46A93256F22}`
  - NetatmoAircareSensor: `{2C9D97B9-D683-51EB-1731-EA98AA7D1130}`
- Nachrichten:
  - `{076043C4-997E-6AB3-9978-DA212D50A9F5}`: an NetatmoAircareIO
  - `{53264646-2842-AA77-59F7-3722D44C2100}`: an NetatmoAircareConfig, NetatmoAircareSensor

## 7. Versions-Historie

- 1.0 @ 19.06.2020 11:34
  - Initiale Version
