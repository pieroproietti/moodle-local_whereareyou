define([
	'core/templates',
	'core/str',
	'core/notification',
], (Templates, Str, Notification) => {
	'use strict';

	class WhereAreYouModal {
		constructor() {
			this.config = null;
			this.modalElement = null;
			this.isLoading = false;
		}

		async init() {
			try {
				this.config = window.M.cfg.whereareyou_config || {};

				// AGGIUNGI QUESTA RIGA DI DEBUG
				console.log('Debug - Config ricevuta:', this.config);
				console.log('Debug - Tutte le chiavi config:', Object.keys(this.config));
				console.log('Debug - Ajax URL:', this.config.ajax_url);
				console.log('Debug - Logout URL:', this.config.logout_url);
				console.log('Debug - window.M.cfg completo:', window.M.cfg);

				await this.loadLanguageStrings();
				await this.renderModal();
				this.attachEventListeners();
				console.log('WhereAreYou Modal inizializzata');
			} catch (error) {
				console.error('Errore inizializzazione WhereAreYou Modal:', error);
				Notification.exception(error);
			}
		}

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
				{key: 'position_student', component: 'local_whereareyou'},
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
					remote: strings[7],
				},
				positions: {
					preside: strings[8],
					teacher: strings[9],
					student: strings[10],
				},
			};
		}

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
						selected: currentDept === 'Pizzicaroli',
					},
					{
						value: 'Gesmundo',
						label: this.strings.departments.gesmundo,
						selected: currentDept === 'Gesmundo',
					},
					{
						value: 'Remoto',
						label: this.strings.departments.remote,
						selected: currentDept === 'Remoto',
					},
				],
				positions: [
					{
						value: 'Preside',
						label: this.strings.positions.preside,
						selected: currentPos === 'Preside',
					},
					{
						value: 'Insegnante',
						label: this.strings.positions.teacher,
						selected: currentPos === 'Insegnante',
					},
					{
						value: 'Alunno',
						label: this.strings.positions.student,
						selected: currentPos === 'Alunno',
					},
				],
			};
		}

		async renderModal() {
			try {
				const templateData = this.prepareTemplateData();
				const html = await Templates.render('local_whereareyou/modal', templateData);
				document.body.insertAdjacentHTML('beforeend', html);
				this.modalElement = document.getElementById('whereareyou-modal-backdrop');
				this.showModal();
			} catch (error) {
				console.error('Errore rendering modal:', error);
				throw error;
			}
		}

		showModal() {
			if (this.modalElement) {
				this.modalElement.style.display = 'flex';
				this.modalElement.offsetHeight;
				this.modalElement.classList.add('show');

				const firstSelect = this.modalElement.querySelector('#whereareyou-department');
				if (firstSelect) {
					firstSelect.focus();
				}
			}
		}

		hideModal() {
			if (this.modalElement) {
				this.modalElement.style.display = 'none';
				this.modalElement.remove();
				this.modalElement = null;
			}
		}

		attachEventListeners() {
			if (!this.modalElement) {
				return;
			}

			const saveBtn = this.modalElement.querySelector('#whereareyou-save-btn');
			if (saveBtn) {
				saveBtn.addEventListener('click', e => {
					e.preventDefault();
					this.handleSave();
				});
			}

			const logoutBtn = this.modalElement.querySelector('#whereareyou-logout-btn');
			if (logoutBtn) {
				logoutBtn.addEventListener('click', e => {
					e.preventDefault();
					this.handleLogout();
				});
			}

			const form = this.modalElement.querySelector('#whereareyou-form');
			if (form) {
				form.addEventListener('submit', e => {
					e.preventDefault();
					this.handleSave();
				});
			}
		}

		async handleSave() {
			if (this.isLoading) {
				return;
			}

			try {
				const department = this.modalElement.querySelector('#whereareyou-department').value;
				const position = this.modalElement.querySelector('#whereareyou-position').value;

				if (!department || !position) {
					await Notification.alert('Attenzione', 'Seleziona sia il dipartimento che la posizione.');
					return;
				}

				this.setLoading(true);
				const success = await this.saveData(department, position);

				if (success) {
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

		async saveData(department, position) {
			try {
				const Ajax = await import('core/ajax');

				const response = await Ajax.call([{
					methodname: 'local_whereareyou_save_data',
					args: {
						department,
						position,
					},
				}]);

				if (response[0] && response[0].success) {
					console.log('Data saved via Web Service:', response[0]);
					return true;
				}

				throw new Error(response[0]?.message || 'Save failed');
			} catch (error) {
				console.error('Web Service Error:', error);
				return await this.saveDataFallback(department, position);
			}
		}

		async saveDataFallback(department, position) {
			try {
				const formData = new FormData();
				formData.append('action', 'save');
				formData.append('department', department);
				formData.append('position', position);
				formData.append('sesskey', window.M.cfg.sesskey);

				const response = await fetch(this.config.ajax_url, {
					method: 'POST',
					body: formData,
					credentials: 'same-origin',
				});

				if (!response.ok) {
					throw new Error(`HTTP error! status: ${response.status}`);
				}

				const data = await response.json();

				if (data.success) {
					console.log('Data saved via fallback AJAX');
					return true;
				}

				throw new Error(data.error || 'Errore sconosciuto');
			} catch (error) {
				console.error('Fallback AJAX Error:', error);
				throw error;
			}
		}

		handleLogout() {
			if (this.isLoading) {
				return;
			}

			if (this.config.logout_url) {
				window.location.href = this.config.logout_url;
			} else {
				console.error('URL logout non configurato');
			}
		}

		setLoading(loading) {
			this.isLoading = loading;

			if (!this.modalElement) {
				return;
			}

			const loadingEl = this.modalElement.querySelector('#whereareyou-loading');
			const saveBtn = this.modalElement.querySelector('#whereareyou-save-btn');
			const logoutBtn = this.modalElement.querySelector('#whereareyou-logout-btn');

			if (loading) {
				if (loadingEl) {
					loadingEl.style.display = 'flex';
				}

				if (saveBtn) {
					saveBtn.disabled = true;
				}

				if (logoutBtn) {
					logoutBtn.disabled = true;
				}
			} else {
				if (loadingEl) {
					loadingEl.style.display = 'none';
				}

				if (saveBtn) {
					saveBtn.disabled = false;
				}

				if (logoutBtn) {
					logoutBtn.disabled = false;
				}
			}
		}
	}

	let modalInstance = null;

	return {
		init() {
			modalInstance ||= new WhereAreYouModal();

			modalInstance.init();
		},

		showModal() {
			modalInstance ||= new WhereAreYouModal();

			modalInstance.init();
		},
	};
});
