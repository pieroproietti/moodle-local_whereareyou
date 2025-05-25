#!/bin/python3
import os
import sys

# Caratteri per disegnare l'albero ASCII
# ├── (tree branch)
# └── (last tree branch)
# │   (vertical line)
#     (indentation space)
TREE_BRANCH = "├── "
TREE_LAST_BRANCH = "└── "
TREE_VERTICAL = "│   "
TREE_INDENT = "    " # Spazi per l'ultimo ramo

# File da riassumere invece di mostrare completamente
SUMMARIZE_FILES = {
    'debug.php': 'File di debug amministrativo per troubleshooting del plugin',
    'test.php': 'Pagina di test per amministratori per testare la modal senza login/logout',
    'settings.php': 'Configurazione amministrativa - aggiunge pagine al menu admin'
}

SUMMARIZE_FILES = {}

# File da escludere completamente
EXCLUDE_FILES = ['.min.js.map', '.min.css.map']

def should_exclude_file(filename, include_minified=False):
    """Determina se un file dovrebbe essere escluso dal sunto"""
    # Escludi sempre i file map
    if any(filename.endswith(ext) for ext in EXCLUDE_FILES):
        return True
    
    # Escludi file minificati se richiesto
    if not include_minified and (filename.endswith('.min.js') or filename.endswith('.min.css')):
        return True
        
    return False

def add_items_to_structure(directory, prefix, lines_list, php_list, js_list, mustache_list, target_directory, include_minified=False):
    """
    Aggiunge ricorsivamente gli elementi di una directory alla lista della struttura
    e raccoglie i file PHP, JS e Mustache.

    Args:
        directory (str): Il percorso della directory corrente da elaborare.
        prefix (str): Il prefisso di indentazione per gli elementi in questa directory.
        lines_list (list): La lista dove aggiungere le stringhe della struttura.
        php_list (list): La lista dove aggiungere i percorsi relativi dei file PHP.
        js_list (list): La lista dove aggiungere i percorsi relativi dei file JS.
        mustache_list (list): La lista dove aggiungere i percorsi relativi dei file Mustache.
        target_directory (str): Il percorso della directory di origine (per calcolare i percorsi relativi).
        include_minified (bool): Se includere file minificati.
    """
    # Ottieni tutti gli elementi (file e directory) nella directory corrente
    try:
        items = os.listdir(directory)
    except OSError as e:
        # Gestisci eventuali errori di accesso alla directory
        lines_list.append(f"{prefix}### Errore accesso directory: {os.path.basename(directory)} ({e})")
        print(f"Attenzione: Errore durante l'accesso alla directory '{directory}' - {e}")
        return # Esci dalla ricorsione per questo ramo

    # Filtra gli elementi nascosti (quelli che iniziano con '.') e la directory 'bin'
    items = [item for item in items if not item.startswith('.') and item.lower() != 'bin']

    # Ordina gli elementi (directory e file) per avere un output coerente
    items.sort()

    # Separa directory e file per elaborare prima le directory (opzionale ma comune)
    dirs = [item for item in items if os.path.isdir(os.path.join(directory, item))]
    files = [item for item in items if os.path.isfile(os.path.join(directory, item))]

    # Ricombina in un unico elenco ordinato (directory prima dei file)
    sorted_items = dirs + files

    # Itera su tutti gli elementi (non nascosti, ordinati)
    for i, item_name in enumerate(sorted_items):
        item_path = os.path.join(directory, item_name)
        is_last_item = (i == len(sorted_items) - 1) # True se questo è l'ultimo elemento nella lista corrente

        # Determina il connettore e il prefisso per il livello successivo
        connector = TREE_LAST_BRANCH if is_last_item else TREE_BRANCH
        next_prefix = prefix + (TREE_INDENT if is_last_item else TREE_VERTICAL) # L'indentazione per i figli

        # Aggiungi la riga per l'elemento corrente
        lines_list.append(f"{prefix}{connector}{item_name}{os.sep if os.path.isdir(item_path) else ''}")

        # Se l'elemento è una directory, chiama ricorsivamente la funzione
        if os.path.isdir(item_path):
            add_items_to_structure(item_path, next_prefix, lines_list, php_list, js_list, mustache_list, target_directory, include_minified)
        # Se è un file di interesse, aggiungilo alla lista appropriata
        elif os.path.isfile(item_path):
            # Controlla se escludere il file
            if should_exclude_file(item_name, include_minified):
                continue
                
            # Calcola il percorso relativo rispetto alla target_directory
            relative_path = os.path.relpath(item_path, target_directory)
            
            if item_name.lower().endswith('.php'):
                php_list.append(relative_path)
            elif item_name.lower().endswith('.js'):
                js_list.append(relative_path)
            elif item_name.lower().endswith('.mustache'):
                mustache_list.append(relative_path)


def write_file_section(f, title, file_list, target_directory, file_type):
    """
    Scrive una sezione per un tipo specifico di file nel report.
    
    Args:
        f: File handle aperto per la scrittura
        title (str): Titolo della sezione
        file_list (list): Lista dei percorsi relativi dei file
        target_directory (str): Directory di base per costruire i percorsi completi
        file_type (str): Tipo di file per il syntax highlighting (es. 'php', 'javascript', 'handlebars')
    """
    f.write(f"# {title}\n\n")
    if file_list:
        for index, file_rel_path in enumerate(file_list):
            f.write(f"{index + 1}. {file_rel_path}\n")
            f.write("\n") # Aggiungi una riga vuota

            # Per leggere il file, dobbiamo ricostruire il percorso completo
            file_full_path = os.path.join(target_directory, file_rel_path)
            filename = os.path.basename(file_rel_path)

            # Controlla se è un file da riassumere
            if filename in SUMMARIZE_FILES:
                f.write(f"**[RIASSUNTO]** - {SUMMARIZE_FILES[filename]}\n\n")
                continue

            # Leggi il contenuto del file
            try:
                with open(file_full_path, 'r', encoding='utf-8') as file_f:
                    file_content = file_f.read()
                    
                # Per file JavaScript molto lunghi, considera un riassunto
                if file_type == 'javascript' and len(file_content) > 10000:  # > 10KB
                    lines = file_content.split('\n')
                    if len(lines) > 200:  # > 200 righe
                        # Mostra solo le prime 50 righe + commento
                        preview_lines = lines[:50]
                        f.write(f"```{file_type}\n")
                        f.write('\n'.join(preview_lines))
                        f.write(f"\n\n// ... [File troncato: {len(lines)} righe totali, prime 50 mostrate] ...\n")
                        f.write("```\n\n")
                        continue
                        
            except Exception as e:
                file_content = f"Errore durante la lettura del file '{file_rel_path}': {e}"
                print(f"Attenzione: impossibile leggere il file '{file_full_path}' - {e}")

            # Scrivi il contenuto all'interno di un blocco di codice con syntax highlighting appropriato
            f.write(f"```{file_type}\n")
            f.write(file_content)
            f.write("\n```\n\n") # Chiudi il blocco di codice e aggiungi righe vuote di separazione
    else:
        f.write(f"Nessun file da riportare per questa sezione.\n\n")


def write_readme_section(f, target_directory):
    """
    Scrive la sezione README.md se il file esiste nella directory root.
    
    Args:
        f: File handle aperto per la scrittura
        target_directory (str): Directory di base dove cercare README.md
    """
    readme_path = os.path.join(target_directory, 'README.md')
    
    f.write("# README.md\n\n")
    
    if os.path.isfile(readme_path):
        try:
            with open(readme_path, 'r', encoding='utf-8') as readme_f:
                readme_content = readme_f.read()
            
            f.write("```markdown\n")
            f.write(readme_content)
            f.write("\n```\n\n")
        except Exception as e:
            f.write(f"Errore durante la lettura del file README.md: {e}\n\n")
            print(f"Attenzione: impossibile leggere il file README.md - {e}")
    else:
        f.write("Nessun file README.md trovato nella directory principale.\n\n")


def write_technical_overview(f, php_files, js_files, mustache_files, target_directory):
    """
    Scrive la sezione panoramica tecnica basata sui file analizzati.
    """
    f.write("# Panoramica Tecnica\n\n")
    
    # Analizza i file per determinare il tipo di plugin
    plugin_type = "Plugin Moodle"
    if any('version.php' in php for php in php_files):
        plugin_type = "Plugin Moodle con versioning"
    if any('hook' in php.lower() for php in php_files):
        plugin_type += " (usa nuovo sistema Hook)"
    
    # Conta template e JS modules
    has_templates = len(mustache_files) > 0
    has_amd_js = any('amd/' in js for js in js_files)
    
    f.write(f"**Tipo**: {plugin_type}\n")
    
    # Determina architettura chiave analizzando i file
    architecture_points = []
    
    if any('hook_callbacks.php' in php for php in php_files):
        architecture_points.append("Hook: Sistema moderno di hook per iniettare funzionalità")
    
    if has_templates:
        architecture_points.append("Template: Mustache per rendering interfacce")
    
    if has_amd_js:
        architecture_points.append("JavaScript: Moduli AMD/RequireJS per funzionalità client-side")
    
    if any('ajax.php' in php for php in php_files):
        architecture_points.append("AJAX: Endpoint per comunicazione asincrona")
    
    if any('install.php' in php for php in php_files):
        architecture_points.append("Installazione: Setup automatico database/configurazione")
    
    if architecture_points:
        f.write("**Architettura chiave**:\n")
        for point in architecture_points:
            f.write(f"- {point}\n")
        f.write("\n")
    
    # File critici
    critical_files = []
    for php in php_files:
        if 'hook_callbacks.php' in php:
            critical_files.append(f"`{php}` - Logica principale hook")
        elif 'ajax.php' in php:
            critical_files.append(f"`{php}` - Endpoint AJAX")
        elif 'version.php' in php:
            critical_files.append(f"`{php}` - Metadata plugin")
    
    for js in js_files:
        if 'amd/src/' in js:
            critical_files.append(f"`{js}` - Modulo JavaScript principale")
    
    for template in mustache_files:
        critical_files.append(f"`{template}` - Template interfaccia")
    
    if critical_files:
        f.write("**File critici**:\n")
        for file_desc in critical_files:
            f.write(f"- {file_desc}\n")
        f.write("\n")


def write_functional_flow(f, php_files, js_files):
    """
    Scrive la sezione flusso funzionale basata sull'analisi dei file.
    """
    f.write("# Flusso Funzionale\n\n")
    
    # Analizza i file per dedurre il flusso
    has_hook = any('hook_callbacks.php' in php for php in php_files)
    has_ajax = any('ajax.php' in php for php in php_files)
    has_modal_js = any('modal.js' in js for js in js_files)
    
    if has_hook and has_ajax and has_modal_js:
        f.write("**Flusso identificato (Modal post-login)**:\n")
        f.write("1. **Hook rileva evento** → inietta JavaScript nell'header della pagina\n")
        f.write("2. **JavaScript si carica** → utilizza RequireJS per caricare moduli\n")
        f.write("3. **Modal si attiva** → mostra interfaccia all'utente\n")
        f.write("4. **Utente interagisce** → seleziona opzioni e conferma\n")
        f.write("5. **Salvataggio AJAX** → invia dati al server per persistenza\n")
        f.write("6. **Controlli sessione** → previene ri-visualizzazione indesiderata\n\n")
    else:
        f.write("**Flusso generico identificato dalla struttura dei file**\n\n")


def generate_folder_summary(target_directory, include_minified=False, include_debug_files=True):
    """
    Genera una rappresentazione ASCII della struttura della cartella (stile 'tree'),
    e riporta il contenuto di README.md, panoramica tecnica, file *.php, *.js e *.mustache.
    Esclude file e directory nascosti (quelli che iniziano con '.') e la directory 'bin'.

    Args:
        target_directory (str): Il percorso della cartella da analizzare.
        include_minified (bool): Se includere file minificati (.min.js, .min.css).
        include_debug_files (bool): Se includere completamente i file di debug/test.
    """
    if not os.path.isdir(target_directory):
        print(f"Errore: La cartella '{target_directory}' non esiste o non è una cartella.")
        return

    # Assicurati che target_directory sia un percorso assoluto per evitare ambiguità
    target_directory = os.path.abspath(target_directory)

    structure_lines = []
    php_files_relative = []
    js_files_relative = []
    mustache_files_relative = []

    # Aggiungi la riga radice all'inizio della struttura visuale
    structure_lines.append(f"{os.path.basename(target_directory)}/")

    # Avvia la generazione della struttura ad albero chiamando la funzione ricorsiva
    add_items_to_structure(target_directory, "", structure_lines, php_files_relative, 
                          js_files_relative, mustache_files_relative, target_directory, include_minified)

    # Ordina i percorsi dei file raccolti
    php_files_relative.sort()
    js_files_relative.sort()
    mustache_files_relative.sort()

    # Se non includere file di debug, rimuovili dalle liste (ma tienili nel riassunto)
    if not include_debug_files:
        # Non rimuoverli, ma il contenuto sarà riassunto dalla funzione write_file_section
        pass

    # Percorso del file di output (SUNTO.md nella directory target)
    output_path = os.path.join(target_directory, 'SUNTO.md')

    # Scrivi il riassunto nel file markdown
    try:
        with open(output_path, 'w', encoding='utf-8') as f:
            # --- Sezione Struttura ---
            f.write(f"# Struttura della cartella '{os.path.basename(target_directory)}'\n\n")
            f.write("```ascii\n") # Usa un blocco di codice per preservare la formattazione ASCII
            f.write("\n".join(structure_lines)) # Scrive le righe della struttura generate
            f.write("\n```\n\n")

            # --- Sezione README.md ---
            write_readme_section(f, target_directory)
            
            # --- Sezione Panoramica Tecnica ---
            # write_technical_overview(f, php_files_relative, js_files_relative, mustache_files_relative, target_directory)
            
            # --- Sezione Flusso Funzionale ---
            # write_functional_flow(f, php_files_relative, js_files_relative)
            
            # --- Sezione File PHP ---
            write_file_section(f, "File PHP", php_files_relative, target_directory, "php")
            
            # --- Sezione File JavaScript ---
            write_file_section(f, "File JavaScript", js_files_relative, target_directory, "javascript")
            
            # --- Sezione Template (se presenti) ---
            if mustache_files_relative:
                write_file_section(f, "Template Mustache", mustache_files_relative, target_directory, "handlebars")

        total_files = len(php_files_relative) + len(js_files_relative) + len(mustache_files_relative)
        readme_exists = os.path.isfile(os.path.join(target_directory, 'README.md'))
        
        print(f"Report generato con successo: '{output_path}' (elementi nascosti e directory 'bin' esclusi).")
        print(f"README.md: {'trovato' if readme_exists else 'non trovato'}")
        print(f"File processati: {len(php_files_relative)} PHP, {len(js_files_relative)} JS, {len(mustache_files_relative)} Mustache (totale: {total_files})")
        if not include_minified:
            print("File minificati esclusi dal report.")

    except IOError as e:
        print(f"Errore durante la scrittura del file '{output_path}': {e}")

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Utilizzo: python sunto.py <percorso_cartella> [--include-minified] [--no-debug-files]")
        print("Esempio: python sunto.py ./local_test")
        print("Opzioni:")
        print("  --include-minified    Includi file .min.js/.min.css")
        print("  --no-debug-files     Riassumi invece di mostrare file debug/test completi")
    else:
        target_dir = sys.argv[1]
        include_minified = '--include-minified' in sys.argv
        include_debug_files = '--no-debug-files' not in sys.argv
        
        generate_folder_summary(target_dir, include_minified, include_debug_files)
        