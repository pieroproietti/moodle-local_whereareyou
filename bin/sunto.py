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

def add_items_to_structure(directory, prefix, lines_list, php_list, target_directory):
    """
    Aggiunge ricorsivamente gli elementi di una directory alla lista della struttura
    e raccoglie i file PHP.

    Args:
        directory (str): Il percorso della directory corrente da elaborare.
        prefix (str): Il prefisso di indentazione per gli elementi in questa directory.
        lines_list (list): La lista dove aggiungere le stringhe della struttura.
        php_list (list): La lista dove aggiungere i percorsi relativi dei file PHP.
        target_directory (str): Il percorso della directory di origine (per calcolare i percorsi relativi).
    """
    # Ottieni tutti gli elementi (file e directory) nella directory corrente
    try:
        items = os.listdir(directory)
    except OSError as e:
        # Gestisci eventuali errori di accesso alla directory
        lines_list.append(f"{prefix}### Errore accesso directory: {os.path.basename(directory)} ({e})")
        print(f"Attenzione: Errore durante l'accesso alla directory '{directory}' - {e}")
        return # Esci dalla ricorsione per questo ramo

    # Filtra gli elementi nascosti (quelli che iniziano con '.')
    items = [item for item in items if not item.startswith('.')]

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
            add_items_to_structure(item_path, next_prefix, lines_list, php_list, target_directory)
        # Se è un file PHP, aggiungilo alla lista dei file PHP
        elif os.path.isfile(item_path) and item_name.lower().endswith('.php'):
             # Calcola il percorso relativo rispetto alla target_directory
            relative_path = os.path.relpath(item_path, target_directory)
            php_list.append(relative_path)


def generate_folder_summary(target_directory):
    """
    Genera una rappresentazione ASCII della struttura della cartella (stile 'tree'),
    elenca i file .php trovati (con percorsi relativi) e riporta il loro contenuto.
    Esclude file e directory nascosti (quelli che iniziano con '.').

    Args:
        target_directory (str): Il percorso della cartella da analizzare.
    """
    if not os.path.isdir(target_directory):
        print(f"Errore: La cartella '{target_directory}' non esiste o non è una cartella.")
        return

    # Assicurati che target_directory sia un percorso assoluto per evitare ambiguità
    target_directory = os.path.abspath(target_directory)

    structure_lines = []
    php_files_relative = []

    # Aggiungi la riga radice all'inizio della struttura visuale
    structure_lines.append(f"{os.path.basename(target_directory)}/")

    # Avvia la generazione della struttura ad albero chiamando la funzione ricorsiva
    # Passiamo la directory target, un prefisso iniziale vuoto, le liste da popolare,
    # e la directory target stessa per i percorsi relativi.
    # La funzione ricorsiva inizierà a listare gli *elementi* nella directory target.
    add_items_to_structure(target_directory, "", structure_lines, php_files_relative, target_directory)


    # Ordina i percorsi dei file PHP relativi raccolti
    php_files_relative.sort()

    # --- CAMBIATO NOME FILE OUTPUT ---
    # Percorso del file di output (SUNTO.md nella directory target)
    output_path = os.path.join(target_directory, 'SUNTO.md')
    # --- FINE CAMBIO NOME ---

    # Scrivi il riassunto nel file markdown
    try:
        with open(output_path, 'w', encoding='utf-8') as f:
            # --- Sezione Struttura ---
            f.write(f"# Struttura della cartella '{os.path.basename(target_directory)}'\n\n")
            f.write("```ascii\n") # Usa un blocco di codice per preservare la formattazione ASCII
            f.write("\n".join(structure_lines)) # Scrive le righe della struttura generate
            f.write("\n```\n\n")

            # --- Sezione Elenco File PHP (percorsi relativi) ---
            f.write("# File PHP trovati (percorsi relativi)\n\n")
            if php_files_relative:
                for php_file_rel_path in php_files_relative:
                    f.write(f"* {php_file_rel_path}\n")
            else:
                f.write("Nessun file *.php trovato in questa cartella e nelle sottocartelle.\n")

            f.write("\n") # Aggiungi una riga vuota per separare le sezioni

            # --- Sezione Contenuto File PHP (con percorsi relativi) ---
            f.write("# Contenuto dei file PHP\n\n")
            if php_files_relative:
                for index, php_file_rel_path in enumerate(php_files_relative):
                    f.write(f"{index + 1}. {php_file_rel_path}\n")
                    f.write("\n") # Aggiungi una riga vuota

                    # Per leggere il file, dobbiamo ricostruire il percorso completo
                    php_file_full_path_to_read = os.path.join(target_directory, php_file_rel_path)

                    # Leggi il contenuto del file
                    try:
                        with open(php_file_full_path_to_read, 'r', encoding='utf-8') as php_f:
                            file_content = php_f.read()
                    except Exception as e:
                        file_content = f"Errore durante la lettura del file '{php_file_rel_path}': {e}"
                        print(f"Attenzione: impossibile leggere il file '{php_file_full_path_to_read}' - {e}")

                    # Scrivi il contenuto all'interno di un blocco di codice PHP Markdown
                    f.write("```php\n")
                    f.write(file_content)
                    f.write("\n```\n\n") # Chiudi il blocco di codice e aggiungi righe vuote di separazione

            else:
                 f.write("Nessun file *.php trovato da riportare.\n")


        print(f"Report generato con successo: '{output_path}' (elementi nascosti esclusi).")

    except IOError as e:
        print(f"Errore durante la scrittura del file '{output_path}': {e}")

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Utilizzo: python nome_script.py <percorso_cartella>")
        print("Esempio: python genera_sunto.py ./local_test")
    else:
        target_dir = sys.argv[1]
        generate_folder_summary(target_dir)