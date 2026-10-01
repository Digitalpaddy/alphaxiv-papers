# alphaXiv Papers

Version: 1.3 (01.10.2026)
Änderung: Wählbarer KI-Anbieter (Anthropic, OpenAI, Mistral, xAI, Gemini, OpenRouter, eigener Dienst) mit Anleitung.

**Idee und Entwicklung: [meiersworld.de](https://www.meiersworld.de)** · Lizenz: GPL-2.0-or-later

---

## Deutsch

### Was das Plugin macht

Unter einem Artikel erscheint eine Box „Papers finden“. Ein Klick genügt: Ein KI-Modell deiner Wahl leitet aus Titel und Text 2–3 englische Suchbegriffe ab, das Plugin sucht damit passende Papers in der arXiv-Datenbank und zeigt sie an. Alle Links führen zum Lesen auf [alphaXiv](https://www.alphaxiv.org). Ergebnisse werden 7 Tage zwischengespeichert.

Du entscheidest pro Artikel, ob die Box erscheint (Checkbox im Editor), denn die Suche passt nicht zu jedem Beitrag.

### Voraussetzungen

- WordPress 5.8 oder neuer, PHP 7.0 oder neuer
- Ein API-Key eines KI-Anbieters: Anthropic (Claude), OpenAI, Mistral, xAI (Grok), Google Gemini, OpenRouter oder ein beliebiger OpenAI-kompatibler Dienst

### Installation

1. `alphaxiv-papers.zip` herunterladen.
2. In WordPress: **Plugins → Installieren → Plugin hochladen**, ZIP auswählen, **Jetzt installieren**, dann **Aktivieren**.
3. **Einstellungen → alphaXiv Papers** öffnen, den KI-Anbieter wählen, API-Key (und außer bei Anthropic den Modellnamen) eintragen und die Inhaltstypen anhaken, für die das Tool gelten soll (z. B. „Beiträge“). Speichern. Mit **Verbindung testen** prüfst du, ob die Anbindung funktioniert.
4. Beitrag im Editor öffnen, in der Seitenleiste bei **alphaXiv Papers** den Haken **Papers-Suche freigeben** setzen, Beitrag aktualisieren. Fertig.

In der Beitragsliste zeigt die Spalte „Papers“, wo die Suche freigegeben ist.

Alternativ zum Eintrag in den Einstellungen kann der Key als Konstante `MW_AXP_API_KEY` (bei Anthropic auch `ANTHROPIC_API_KEY`) in der `wp-config.php` stehen. Das ist sicherer, weil der Key dann nicht in der Datenbank liegt.

### Einstellungen

| Einstellung | Bedeutung |
| --- | --- |
| KI-Anbieter, API-Key, Modell | Welcher Anbieter die Suchbegriffe erzeugt. Bei Anthropic ist `claude-haiku-4-5-20251001` voreingestellt, bei allen anderen trägst du den Modellnamen selbst ein. Ein kleines, günstiges Modell reicht |
| Basis-URL | Nur bei „Eigener Anbieter“ |
| Inhaltstypen | Für welche Typen die Freigabe-Checkbox erscheint, mit Sprache je Typ (automatisch, Deutsch, English) |
| Standard | Ob Artikel ohne gesetzte Freigabe automatisch freigegeben sind (empfohlen: aus) |
| Limits | Suchen pro IP und Stunde, neue KI-Suchen pro Tag insgesamt, Papers pro Suche |
| Cloudflare | Anhaken, wenn die Seite hinter Cloudflare liegt, sonst teilen sich alle Besucher ein Limit |
| Credit | Kleiner Hinweis „Idee: meiersworld.de“ in der Box |

Die Sprache der Box folgt standardmäßig der Websprache (mit Polylang der Artikelsprache). Per Filter `mw_axp_lang` lässt sie sich überschreiben.

### Anderen KI-Anbieter als Anthropic nutzen

1. Unter **Einstellungen → alphaXiv Papers** den **KI-Anbieter** auswählen: OpenAI, Mistral AI, xAI (Grok), Google Gemini oder OpenRouter. Die Adresse der Schnittstelle ist dafür bereits hinterlegt.
2. Den **API-Key** des Anbieters eintragen (im Konto des Anbieters erstellen).
3. Den **Modellnamen** eintragen, genau so, wie er in der Modellliste des Anbieters steht. Ein kleines, günstiges Modell reicht, die Aufgabe ist einfach. Modellnamen ändern sich bei den Anbietern häufig, deshalb gibt es hier keine Voreinstellung.
4. Speichern und **Verbindung testen** klicken. Bei einem Fehler zeigt der Test die Meldung des Anbieters (z. B. falscher Key oder unbekanntes Modell).

**Anbieter, der nicht in der Liste steht:** „Eigener Anbieter (OpenAI-kompatibel)“ wählen und die **Basis-URL** eintragen, bis einschließlich `/v1`, zum Beispiel `https://api.groq.com/openai/v1`. Das Plugin ruft dann `<Basis-URL>/chat/completions` im OpenAI-Format auf, das viele Dienste anbieten. Erlaubt sind nur `https`-Adressen; `http` nur für `localhost` (z. B. ein lokal auf demselben Server laufendes Modell).

**Hinweis zur Qualität:** Das Modell soll eine kurze JSON-Antwort liefern. Kleine oder sehr schwache Modelle halten sich manchmal nicht daran. Das Plugin zeigt dann „Die Suche ist fehlgeschlagen“. In dem Fall ein etwas größeres Modell wählen.

### Kosten

Jede neue Suche ist eine kurze Anfrage an das gewählte KI-Modell. Wiederholte Aufrufe desselben Artikels kommen aus dem Cache und kosten nichts. Mit dem Tageslimit behältst du die Kosten im Griff.

### Datenschutz (bitte in deiner Datenschutzerklärung berücksichtigen)

- Beim Klick auf „Papers finden“ sendet dein Server Titel und die ersten ca. 6.000 Zeichen des Artikels an den von dir gewählten KI-Anbieter (z. B. Anthropic). Sein Datenschutz und sein Vertragsstand gelten für diese Übertragung, bei Anbietern außerhalb der EU auch die Drittland-Regeln. Besucherdaten werden dabei nicht übertragen.
- Die erzeugten Suchbegriffe gehen von deinem Server an die arXiv-API. Dort erscheint die IP deines Servers, nicht die des Besuchers.
- Die Ergebnis-Links öffnen alphaXiv bzw. arXiv in einem neuen Tab. Ab dann gelten deren Datenschutzbestimmungen.
- Die IP-Adresse des Besuchers wird nur als Hash für das Stunden-Limit zwischengespeichert.

arXiv bittet darum, die Nutzung seiner API zu erwähnen: „Thank you to arXiv for use of its open access interchange.“

### Deinstallation

Plugin deaktivieren und löschen. Einstellungen und Zwischenspeicher werden entfernt, die Freigabe-Markierungen an den Artikeln bleiben erhalten.

### Für Entwickler

Filter: `mw_axp_enabled` (Freigabe), `mw_axp_lang` (Sprache), `mw_axp_post_types` (Typen), `mw_axp_client_ip` (IP-Ermittlung).

---

## English

### What it does

A "Find papers" box appears below an article. One click: an AI model of your choice derives 2–3 English search phrases from the title and text, the plugin searches the arXiv database with them and lists matching papers. All links lead to [alphaXiv](https://www.alphaxiv.org) for reading. Results are cached for 7 days.

You decide per article whether the box appears (checkbox in the editor), because the search does not fit every post.

### Requirements

- WordPress 5.8+, PHP 7.0+
- An API key from an AI provider: Anthropic (Claude), OpenAI, Mistral, xAI (Grok), Google Gemini, OpenRouter or any OpenAI-compatible service

### Installation

1. Download `alphaxiv-papers.zip`.
2. In WordPress: **Plugins → Add New → Upload Plugin**, choose the ZIP, **Install Now**, then **Activate**.
3. Open **Settings → alphaXiv Papers**, choose the AI provider, enter its API key (and the model name, except for Anthropic) and tick the content types the tool should apply to (e.g. "Posts"). Save. Use **Test connection** to check that the connection works.
4. Open a post in the editor, tick **Enable papers search** in the **alphaXiv Papers** sidebar box and update the post. Done.

The "Papers" column in the posts list shows where the search is enabled.

Instead of the settings field, the key can also be defined as the constant `MW_AXP_API_KEY` (for Anthropic also `ANTHROPIC_API_KEY`) in `wp-config.php`. That is safer because the key is then not stored in the database.

### Settings

| Setting | Meaning |
| --- | --- |
| AI provider, API key, model | Which provider generates the search phrases. For Anthropic `claude-haiku-4-5-20251001` is preset, for all others you enter the model name yourself. A small, cheap model is enough |
| Base URL | Only for "Custom provider" |
| Content types | Which types get the release checkbox, with a language per type (automatic, Deutsch, English) |
| Default | Whether articles without an explicit release are enabled automatically (recommended: off) |
| Limits | Searches per IP and hour, new AI searches per day in total, papers per search |
| Cloudflare | Tick if your site is behind Cloudflare, otherwise all visitors share one limit |
| Credit | Small "Idea: meiersworld.de" note in the box |

The box language follows the site language by default (the post language with Polylang). Override it with the `mw_axp_lang` filter.

### Using a provider other than Anthropic

1. Under **Settings → alphaXiv Papers** choose the **AI provider**: OpenAI, Mistral AI, xAI (Grok), Google Gemini or OpenRouter. The API address is already built in.
2. Enter the provider's **API key** (create it in your provider account).
3. Enter the **model name** exactly as it appears in the provider's model list. A small, cheap model is enough, the task is simple. Providers rename models often, so there is no preset here.
4. Save and click **Test connection**. On failure the test shows the provider's message (e.g. wrong key or unknown model).

**A provider that is not listed:** choose "Custom provider (OpenAI-compatible)" and enter the **base URL** up to and including `/v1`, for example `https://api.groq.com/openai/v1`. The plugin then calls `<base URL>/chat/completions` in the OpenAI format, which many services offer. Only `https` addresses are accepted; `http` only for `localhost` (e.g. a model running on the same server).

**Quality note:** The model must return a short JSON answer. Very small or weak models sometimes ignore that. The plugin then shows "The search failed". In that case pick a somewhat larger model.

### Costs

Each new search is one short request to the selected AI model. Repeated views of the same article are served from the cache at no cost. The daily limit keeps costs under control.

### Privacy (please reflect this in your privacy policy)

- When a visitor clicks "Find papers", your server sends the title and the first ~6,000 characters of the article to the AI provider you selected (e.g. Anthropic). Its privacy terms and contract status apply to this transfer, including third-country rules for providers outside the EU. No visitor data is transmitted.
- The generated search phrases go from your server to the arXiv API, which sees your server's IP, not the visitor's.
- Result links open alphaXiv or arXiv in a new tab; their privacy terms apply from there.
- The visitor's IP is only stored as a hash for the hourly limit.

arXiv asks API users to acknowledge the service: "Thank you to arXiv for use of its open access interchange."

### Uninstall

Deactivate and delete the plugin. Settings and cache are removed; the per-article release flags stay in place.

### For developers

Filters: `mw_axp_enabled` (release), `mw_axp_lang` (language), `mw_axp_post_types` (types), `mw_axp_client_ip` (IP detection).

---

© 2026 [meiersworld.de](https://www.meiersworld.de) · GPL-2.0-or-later. Please keep the credit notice intact.
