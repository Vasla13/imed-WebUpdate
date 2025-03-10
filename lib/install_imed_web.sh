#!/bin/bash
#
# install_imed_web.sh : Installationsskript für Imed-Web
# Verwendung: sh install_imed_web.sh /pfad/zum/archiv.tgz [SCHRITT]
#        SCHRITT=1 : Archiv extrahieren
#        SCHRITT=2 : Ausführung von install.sh im extrahierten Ordner
#        SCHRITT=3 : Keine Aktion (Webzugriff)
#
set -e
set -o pipefail

WEB_ARCHIV="$1"
SCHRITT="$2"

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

case "$SCHRITT" in
  1)
    echo "Starte Extraktion..."
    mkdir -p /imed/prog/new
    ARCHIVE_NAME=$(basename "$WEB_ARCHIV")
    cp "$WEB_ARCHIV" "/imed/prog/new/$ARCHIVE_NAME"
    tar -xzf "/imed/prog/new/$ARCHIVE_NAME" -C /imed/prog/new/
    echo "Extraktion abgeschlossen."
    ;;
  2)
    echo "Starte Konfiguration..."
    EXTRACTED_DIR=$(find /imed/prog/new -maxdepth 1 -type d -name "imed-Web_*" | sort | head -n 1)
    if [ -n "$EXTRACTED_DIR" ]; then
       if [ -f "$EXTRACTED_DIR/install.sh" ]; then
          echo "Führe $EXTRACTED_DIR/install.sh aus..."
          cd "$EXTRACTED_DIR"
          sh install.sh
          echo "Konfiguration abgeschlossen."
          exit 0
       else
          echo "FEHLER: install.sh wurde in $EXTRACTED_DIR nicht gefunden."
          exit 1
       fi
    else
       echo "FEHLER: Kein Ordner mit 'imed-Web_*' gefunden."
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
