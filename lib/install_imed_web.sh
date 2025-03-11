#!/bin/bash
#
# install_imed_web.sh : Installationsskript für Imed-Web
# Nutzung: sh install_imed_web.sh /pfad/zum/archiv.tgz [SCHRITT] [TARGET_CONTAINER]
#        SCHRITT=1 : Archiv extrahieren
#        SCHRITT=2 : Ausführung von install.sh im extrahierten Ordner
#        SCHRITT=3 : Keine Aktion (Webzugriff)
#
set -e
set -o pipefail

WEB_ARCHIV="$1"
SCHRITT="$2"
TARGET_CONTAINER="$3"

if [ -z "$WEB_ARCHIV" ]; then
    echo "FEHLER: Kein Archivpfad als Argument übergeben."
    exit 1
fi

if [ ! -f "$WEB_ARCHIV" ]; then
    echo "FEHLER: Datei nicht gefunden: $WEB_ARCHIV"
    exit 1
fi

echo "=== Start der Installation von Imed-Web - Schritt $SCHRITT ==="
echo "Zu bearbeitendes Archiv: $WEB_ARCHIV"
echo "Zielcontainer: $TARGET_CONTAINER"

case "$SCHRITT" in
  1)
    echo "Starte Extraktion..."
    # Kopiere das Archiv in den Zielcontainer (optional)
    ARCHIVE_NAME=$(basename "$WEB_ARCHIV")
    cp "$WEB_ARCHIV" "$TARGET_CONTAINER/$ARCHIVE_NAME"
    # Archiv im Zielcontainer entpacken, ohne den Ordner umzubenennen
    tar -xzf "$TARGET_CONTAINER/$ARCHIVE_NAME" -C "$TARGET_CONTAINER"
    echo "Extraktion abgeschlossen."
    ;;
  2)
    echo "Starte Konfiguration..."
    if [ -d "$TARGET_CONTAINER" ]; then
         cd "$TARGET_CONTAINER" || exit 1
         if [ -f install.sh ]; then
              echo "Führe install.sh in $TARGET_CONTAINER aus..."
              sh install.sh
              echo "Konfiguration abgeschlossen."
         else
              echo "FEHLER: install.sh nicht gefunden in $TARGET_CONTAINER."
              exit 1
         fi
    else
         echo "FEHLER: Zielcontainer $TARGET_CONTAINER existiert nicht."
         exit 1
    fi
    ;;
  3)
    echo "Schritt 3: Keine Aktion (Webzugriff)."
    ;;
  *)
    echo "FEHLER: Unbekannter Schritt: $SCHRITT"
    exit 1
    ;;
esac

exit 0
