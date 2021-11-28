# Forum\_XH

Forum\_XH ermöglicht das Einbinden von einfachen Foren auf einer
CMSimple\_XH Website. Zur Benutzerverwaltung dient
[Memberpages](https://github.com/cmsimple-xh/memberpages) oder
[Register\_XH](https://github.com/cmb69/register_xh).

Das Posten erfordert als Mitglied angemeldet zu sein, auch wenn die
Foren öffentlich eingesehen werden können. Auf diese Weise sind keine
weiteren Vorsorgen nötig um Spam zu verhindern, und das Posten erfordert
keine zusätzlichen Eingaben wie Benutzername, E-Mail-Adresse usw.

Forum\_XH bietet einen Editor, der einfache
[BBCode](https://de.wikipedia.org/wiki/BBCode) Auszeichnungen versteht.
Die Daten werden in Flat-Files gespeichert, die durch pessimistisches
Sperren des gesamten Forums vor gleichzeitigem Zugriff geschützt sind,
so dass Forum\_XH nicht für stark frequentierte Foren geeignet ist.
Weiterhin fehlen typische fortgeschrittene Features wie
Benutzersignaturen, PN, das Abonnieren von Themen usw.

## Inhaltsverzeichnis

- [Voraussetzungen](#voraussetzungen)
- [Download](#download)
- [Installation](#installation)
- [Einstellungen](#einstellungen)
- [Verwendung](#verwendung)
  - [BBCode](#bbcode)
- [Fehlerbehebung](#fehlerbehebung)
- [Lizenz](#lizenz)
- [Danksagung](#danksagung)

## Voraussetzungen

Forum\_XH ist ein Plugin für CMSimple\_XH.
Es benötigt CMSimple_XH ≥ 1.7.0
mit dem [Fa\_XH Plugin](https://github.com/cmb69/fa_xh)
und dem [Plib_XH Plugin](https://github.com/cmb69/plib_xh).
Es benötigt PHP ≥ 7.0.0 mit den JSON und Session Extensions.

## Download

Das [aktuelle Release](https://github.com/cmb69/forum_xh/releases/latest)
kann von Github herunter geladen werden.

## Installation

Die Installation erfolgt wie bei vielen anderen CMSimple\_XH-Plugins
auch. Im [CMSimple\_XH
Wiki](https://wiki.cmsimple-xh.org/doku.php/de:installation) finden Sie
ausführliche Hinweise.

1. Sichern Sie die Daten auf Ihrem Server.
1. Entpacken Sie die ZIP-Datei auf Ihrem Rechner.
1. Laden Sie das ganze Verzeichnis `forum/` auf Ihren Server in
   das `plugins/` Verzeichnis von CMSimple\_XH hoch.
1. Machen Sie die Unterverzeichnisse `css/`, `config/` und
   `languages/` beschreibbar.
1. Browsen Sie zu `Plugins` → `Forum` im Administrationsbereich,
   um zu prüfen, ob alle Voraussetzungen erfüllt sind.

## Einstellungen

Die Konfiguration des Plugins erfolgt wie bei vielen anderen
CMSimple\_XH-Plugins auch im Administrationsbereich der Website. Wählen
Sie `Plugins` → `Forum`.

Sie können die Original-Einstellungen von Form\_XH unter `Konfiguration`
ändern. Beim Überfahren der Hilfe-Icons mit der Maus werden Hinweise zu
den Einstellungen angezeigt.

Die Lokalisierung wird unter `Sprache` vorgenommen. Sie können die
Zeichenketten in Ihre eigene Sprache übersetzen, falls keine
entsprechende Sprachdatei zur Verfügung steht, oder sie entsprechend
Ihren Anforderungen anpassen.

Das Aussehen von Forum\_XH kann unter `Stylesheet` angepasst werden.

## Verwendung

Das Einfügen eines Forums auf einer Seite erfolgt mit dem Pluginaufruf:

    {{{forum('name-des-forums')}}}

Sie können so viele Foren verwenden wie Sie möchten, aber höchstens eins
pro CMSimple\_XH Seite. Die Foren werden durch ihre Namen unterschieden,
die nur Kleinbuchstaben (`a`-`z`), Ziffern (`0`-`9`) und Minuszeichen
(`-`) enthalten dürfen.

Nach dem Wechsel in den Ansichtsmodus sehen Sie das Forum, und falls es
bereits Themen enthält, können Sie durch diese navigieren. Das Posten
von neuen Kommentaren erfordert, dass Sie per
[Memberpages](https://github.com/cmsimple-xh/memberpages) oder
[Register\_XH](https://github.com/cmb69/register_xh) angemeldet sind.
Dann können Sie ebenfalls Ihre eigenen Posts bearbeiten und löschen. Der
CMSimple\_XH Administrator kann die Posts aller Benutzer bearbeiten und
löschen, aber um neue Kommentare zu schreiben, muss der Administrator
zusätzlich als Mitglied angemeldet sein. In der Plugin-Administration
wird keine zusätzliche Verwaltungsfunktionalität angeboten.

### BBCode

Damit Benutzer Ihre Kommentare auszeichnen können, ist grundlegender
BBCode verfügbar. Die folgenden Elemente werden unterstützt:

- `[b]Fettschrift[/b]`:
  **Fettschrift**
- `[i]Kursivschrift[/i]`:
  *Kursivschrift*
- `[u]unterstrichener Text[/u]`:
  <u>unterstrichener Text</u>
- `[s]durchgestrichener Text[/s]`:
  ~~durchgestrichener Text~~
- `[url]https://cmsimple-xh.org/[/url]`:
  <https://cmsimple-xh.org/>
- `[url=https://cmsimple-xh.org/]CMSimple\_XH[/url]`:
  [CMSimple\_XH](https://cmsimple-xh.org/)
- `[img]https://cmsimple-xh.org/userfiles/images/flags/de.gif[/img]`:
  ![](https://cmsimple-xh.org/userfiles/images/flags/de.gif)
- `[iframe]https://cmsimple-xh.org/userfiles/images/flags/de.gif[/iframe]`:
  ![](https://cmsimple-xh.org/userfiles/images/flags/de.gif)  
  *Iframes werden verwendet, um externe Inhalte einzubinden, z.B. Youtube-Videos.*
- `[size=150]große Schrift[/size]`:
  <span style="font-size:150%">große Schrift</span>
- `[list][*]Eins [*]Zwei[/list]`:
  - Eins
  - Zwei
- `[list=1][*]Eins [*]Zwei[/list]`:
  1. Eins
  1. Zwei
- `[quote]Zitierter Text[/quote]`:
  <blockquote>Zitierter Text</blockquote>
- `[code]dicktengleiche Schrift[/code]`:
  `dicktengleiche Schrift`

Beachten Sie, dass die Verschachtelung von gleichartigen Elementen nicht
erlaubt ist (z.B. sind verschachtelte Listen nicht möglich). Das HTML,
das vom BBCode Konverter erzeugt wird, ist immer gültig und sicher (d.h.
Script-Injektion ist nicht möglich). Falls die Verschachtelung von
BBCode Elementen in ungültigem HTML resultieren würde, dann werden die
Problemelemente einfach ignoriert.

Die Verwendung des Auszeichnungseditors sollte weitgehend
selbsterklärend sein. Bitte beachten Sie, dass eine einfache Vorschau
über den Schalter ganz rechts verfügbar ist. Wenn JavaScript im Browser
des Benutzers nicht verfügbar ist, dann ist der Editor nicht verfügbar,
aber die Verwendung von BBCode in der Textarea ist dennoch möglich.

## Fehlerbehebung

Melden Sie Programmfehler und stellen Sie Supportanfragen entweder auf
[Github](https://github.com/cmb69/forum_xh/issues)
oder im [CMSimple\_XH Forum](https://cmsimpleforum.com/).

## Lizenz

Forum\_XH ist freie Software. Sie können es unter den Bedingungen der
GNU General Public License, wie von der Free Software Foundation
veröffentlicht, weitergeben und/oder modifizieren, entweder gemäß
Version 3 der Lizenz oder (nach Ihrer Option) jeder späteren Version.

Die Veröffentlichung von Forum\_XH erfolgt in der Hoffnung, daß es Ihnen
von Nutzen sein wird, aber *ohne irgendeine Garantie*, sogar ohne die
implizite Garantie der *Marktreife* oder der *Verwendbarkeit für einen
bestimmten Zweck*. Details finden Sie in der GNU General Public License.

Sie sollten ein Exemplar der GNU General Public License zusammen mit
Forum\_XH erhalten haben. Falls nicht, siehe
<https://www.gnu.org/licenses/>.

© 2012-2021 Christoph M. Becker

Dänische Übersetzung © 2012 Jens Maegard  
Russische Übersetzung © 2012 Lybomyr Kydray

## Danksagung

Das Pluginlogo wurde von [Dezinerfolio](https://www.dezinerfolio.com/)
gestaltet. Vielen Dank für die Veröffentlichung des Icons als Freeware.

Die Emoticons wurden dem [LED icon pack](http://led24.de/) entnommen.
Vielen Dank für die Veröffentlichung dieser Icons unter CC BY-SA.

Vielen Dank an die Gemeinde im [CMSimple\_XH
Forum](https://www.cmsimpleforum.com/)</a> für Hinweise, Vorschläge und
das Testen. Besonders möchte ich *Traktorist* und *Old* für viele gute
Vorschläge danken.
Ebenfalls vielen Dank an *Ulrich* für die Wiederbelebung des Plugins,
und an *lck* für das Beisteuern der Iframe Markup Unterstützung.

Und zu guter letzt vielen Dank an [Peter
Harteg](https://www.harteg.dk/), den „Vater“ von CMSimple, und alle
Entwickler von [CMSimple\_XH](https://www.cmsimple-xh.org/), ohne die
dieses phantastische CMS nicht existieren würde.
