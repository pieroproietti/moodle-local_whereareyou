/**
 * WhereAreYou Modal - Modulo JavaScript moderno (senza jQuery)
 * 
 * @module local_whereareyou/modal
 * @copyright 2025
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/templates',
    'core/str',
    'core/notification'
], function(Templates, Str, Notification) {
    'use strict';

    /**
     * Classe principale per gestire la modal WhereAreYou
     */
    class WhereAreYouModal {
        
        constructor() {
            this.config = null;
            this.modalElement = null;
            this.isLoading = false;
        }

        /**
         * Inizializza il modulo
         */
        async init() {
            try {
                // Recupera configurazione passata da PHP
                this.config = window.M.cfg.whereareyou_config || {};
                
                // Carica le stringhe di lingua
                await this.loadLanguageStrings();
                
                // Renderizza e mostra la modal
                await this.renderModal();
                
                // Aggiunge event listeners
                this.attachEventListeners();
                
                console.log('WhereAreYou Modal inizializzata');
                
            } catch (error) {
                console.error('Errore inizializzazione WhereAreYou Modal:', error);
                Notification.exception(error);
            }
        }

        /**
         * Carica le stringhe di lingua
         */
        async loadLanguageStrings() {
            const strings = await Str.get_strings([
                {key: 'modal_title', component: 'local_whereareyou'},
                {key: 'department', component: 'local_whereareyou'},
                {key: 'position', component: 'local_whereareyou'},
                {key: 'save', component: 'local_whereareyou'},
                {key: 'logout', component: 'local_whereareyou'},
                {key: 'department_pizzicaroli', component: 'local_whereareyou'},
                {key: 'department_gesmundo', component: 'local_whereareyou'},
                {key: 'department_remote', component: 'local_whereareyou'},
                {key: 'position_preside', component: 'local_whereareyou'},
                {key: 'position_teacher', component: 'local_whereareyou'},
                {key: 'position_student', component: 'local_whereareyou'}
            ]);

            this.strings = {
                modal_title: strings[0],
                department_label: strings[1],
                position_label: strings[2],
                save_label: strings[3],
                logout_label: strings[4],
                departments: {
                    pizzicaroli: strings[5],
                    gesmundo: strings[6],
                    remote: strings[7]
                },
                positions: {
                    preside: strings[8],
                    teacher: strings[9],
                    student: strings[10]
                }
            };
        }

        /**
         * Prepara i dati per il template
         */
        prepareTemplateData() {
            const currentDept = this.config.current_department || '';
            const currentPos = this.config.current_position || '';

            return {
                modal_title: this.strings.modal_title,
                department_label: this.strings.department_label,
                position_label: this.strings.position_label,
                save_label: this.strings.save_label,
                logout_label: this.strings.logout_label,
                departments: [
                    {
                        value: 'Pizzicaroli',
                        label: this.strings.departments.pizzicaroli,
                        selected: currentDept === 'Pizzicaroli'
                    },
                    {
                        value: 'Gesmundo',
                        label: this.strings.departments.gesmundo,
                        selected: currentDept === 'Gesmundo'
                    },
                    {
                        value: 'Remoto',
                        label: this.strings.departments.remote,
                        selected: currentDept === 'Remoto'
                    }
                ],
                positions: [
                    {
                        value: 'Preside',
                        label: this.strings.positions.preside,
                        selected: currentPos === 'Preside'
                    },
                    {
                        value: 'Insegnante',
                        label: this.strings.positions.teacher,
                        selected: currentPos === 'Insegnante'
                    },
                    {
                        value: 'Alunno',
                        label: this.strings.positions.student,
                        selected: currentPos === 'Alunno'
                    }
                ]
            };
        }

        /**
         * Renderizza la modal usando il template Mustache
         */
        async renderModal() {
            try {
                const templateData = this.prepareTemplateData();
                
                // Renderizza il template
                const html = await Templates.render('local_whereareyou/modal', templateData);
                
                // Aggiunge al DOM
                document.body.insertAdjacentHTML('beforeend', html);
                
                // Salva riferimento all'elemento
                this.modalElement = document.getElementById('whereareyou-modal-backdrop');
                
                // Mostra la modal con animazione
                this.showModal();
                
            } catch (error) {
                console.error('Errore rendering modal:', error);
                throw error;
            }
        }

        /**
         * Mostra la modal
         */
        showModal() {
            if (this.modalElement) {
                this.modalElement.style.display = 'flex';
                // Forza il reflow per attivare l'animazione CSS
                this.modalElement.offsetHeight;
                this.modalElement.classList.add('show');
                
                // Focus sul primo campo
                const firstSelect = this.modalElement.querySelector('#whereareyou-department');
                if (firstSelect) {
                    firstSelect.focus();
                }
            }
        }

        /**
         * Nasconde la modal
         */
        hideModal() {
            if (this.modalElement) {
                this.modalElement.style.display = 'none';
                this.modalElement.remove();
                this.modalElement = null;
            }
        }

        /**
         * Aggiunge event listeners
         */
        attachEventListeners() {
            if (!this.modalElement) return;

            // Bottone Salva
            const saveBtn = this.modalElement.querySelector('#whereareyou-save-btn');
            if (saveBtn) {
                saveBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleSave();
                });
            }

            // Bottone Logout
            const logoutBtn = this.modalElement.querySelector('#whereareyou-logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    this.handleLogout();
                });
            }

            // Submit del form
            const form = this.modalElement.querySelector('#whereareyou-form');
            if (form) {
                form.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.handleSave();
                });
            }

            // Escape key per chiudere (opzionale - al momento non implementato)
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.modalElement && !this.isLoading) {
                    // Nota: per ora non chiudiamo con Escape perché vogliamo che l'utente scelga
                    console.log('Escape premuto - modal rimane aperta');
                }
            });
        }

        /**
         * Gestisce il salvataggio dei dati
         */
        async handleSave() {
            if (this.isLoading) return;

            try {
                // Recupera i valori
                const department = this.modalElement.querySelector('#whereareyou-department').value;
                const position = this.modalElement.querySelector('#whereareyou-position').value;

                // Validazione client-side
                if (!department || !position) {
                    await Notification.alert('Attenzione', 'Seleziona sia il dipartimento che la posizione.');
                    return;
                }

                // Mostra loading
                this.setLoading(true);

                // Invia i dati via AJAX
                const success = await this.saveData(department, position);

                if (success) {
                    // Successo - nascondi modal
                    this.hideModal();
                    await Notification.alert('Successo', 'Dati salvati correttamente!');
                } else {
                    throw new Error('Salvataggio fallito');
                }

            } catch (error) {
                console.error('Errore salvataggio:', error);
                await Notification.exception(error);
            } finally {
                this.setLoading(false);
            }
        }

        /**
         * Invia dati al server via AJAX
         */
        async saveData(department, position) {
            try {
                const formData = new FormData();
                formData.append('action', 'save');
                formData.append('department', department);
                formData.append('position', position);
                formData.append('sesskey', window.M.cfg.sesskey);

                const response = await fetch(this.config.ajax_url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();
                
                if (data.success) {
                    return true;
                } else {
                    throw new Error(data.error || 'Errore sconosciuto');
                }

            } catch (error) {
                console.error('Errore AJAX:', error);
                throw error;
            }
        }

        /**
         * Gestisce il logout
         */
        handleLogout() {
            if (this.isLoading) return;
            
            // Redirect alla pagina di logout
            if (this.config.logout_url) {
                window.location.href = this.config.logout_url;
            } else {
                console.error('URL logout non configurato');
            }
        }

        /**
         * Mostra/nasconde stato di loading
         */
        setLoading(loading) {
            this.isLoading = loading;
            
            if (!this.modalElement) return;

            const loadingEl = this.modalElement.querySelector('#whereareyou-loading');
            const saveBtn = this.modalElement.querySelector('#whereareyou-save-btn');
            const logoutBtn = this.modalElement.querySelector('#whereareyou-logout-btn');

            if (loading) {
                if (loadingEl) loadingEl.style.display = 'flex';
                if (saveBtn) saveBtn.disabled = true;
                if (logoutBtn) logoutBtn.disabled = true;
            } else {
                if (loadingEl) loadingEl.style.display = 'none';
                if (saveBtn) saveBtn.disabled = false;
                if (logoutBtn) logoutBtn.disabled = false;
            }
        }
    }

    // Istanza singola del modulo
    let modalInstance = null;

    return {
        /**
         * Punto di ingresso del modulo
         */
        init: function() {
            if (!modalInstance) {
                modalInstance = new WhereAreYouModal();
            }
            modalInstance.init();
        },

        /**
         * Per testing - mostra modal programmaticamente
         */
        showModal: function() {
            if (!modalInstance) {
                modalInstance = new WhereAreYouModal();
            }
            modalInstance.init();
        }
    };
});