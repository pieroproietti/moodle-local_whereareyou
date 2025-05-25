<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();
admin_externalpage_setup('local_whereareyou_test');

$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_title(get_string('testpage', 'local_whereareyou'));
$PAGE->set_heading(get_string('testpage', 'local_whereareyou'));

$action = optional_param('action', '', PARAM_ALPHA);

if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'reset':
            handle_reset_user_data();
            redirect($PAGE->url, 'Dati utente resettati!', null, \core\output\notification::NOTIFY_SUCCESS);
            break;
            
        case 'clear_session':
            unset($SESSION->whereareyou_modal_shown);
            redirect($PAGE->url, 'Flag sessione rimosso!', null, \core\output\notification::NOTIFY_SUCCESS);
            break;
    }
}

function get_current_user_department() {
    global $DB, $USER;
    $field = $DB->get_record('user_info_field', ['shortname' => 'whereareyou_department']);
    if (!$field) return '';
    $data = $DB->get_record('user_info_data', ['userid' => $USER->id, 'fieldid' => $field->id]);
    return $data ? $data->data : '';
}

function get_current_user_position() {
    global $DB, $USER;
    $field = $DB->get_record('user_info_field', ['shortname' => 'whereareyou_position']);
    if (!$field) return '';
    $data = $DB->get_record('user_info_data', ['userid' => $USER->id, 'fieldid' => $field->id]);
    return $data ? $data->data : '';
}

function handle_reset_user_data() {
    global $DB, $USER;
    
    $dept_field = $DB->get_record('user_info_field', ['shortname' => 'whereareyou_department']);
    $pos_field = $DB->get_record('user_info_field', ['shortname' => 'whereareyou_position']);
    
    if ($dept_field) {
        $DB->delete_records('user_info_data', ['userid' => $USER->id, 'fieldid' => $dept_field->id]);
    }
    
    if ($pos_field) {
        $DB->delete_records('user_info_data', ['userid' => $USER->id, 'fieldid' => $pos_field->id]);
    }
}

list($current_dept, $current_pos) = [get_current_user_department(), get_current_user_position()];

// DATI DIRETTAMENTE IN JAVASCRIPT - BYPASS data_for_js
$ajax_url = (new moodle_url('/local/whereareyou/ajax.php'))->out(false);
$logout_url = (new moodle_url('/login/logout.php', ['sesskey' => sesskey()]))->out(false);

$js = "
// INIETTIAMO I DATI DIRETTAMENTE
window.M.cfg.whereareyou_config = {
    'ajax_url': '{$ajax_url}',
    'logout_url': '{$logout_url}',
    'current_department': '{$current_dept}',
    'current_position': '{$current_pos}'
};

console.log('Dati iniettati direttamente:', window.M.cfg.whereareyou_config);

require(['local_whereareyou/modal'], function(Modal) {
    document.getElementById('show-modal-btn').addEventListener('click', function() {
        Modal.showModal();
    });
});
";

$PAGE->requires->js_init_code($js);

echo $OUTPUT->header();
?>

<div class="container-fluid">
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h3><i class="fa fa-flask"></i> <?php echo get_string('testpage', 'local_whereareyou'); ?></h3>
        </div>
        <div class="card-body">
            <p>Questa pagina permette di testare la funzionalità del plugin WhereAreYou.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h4><i class="fa fa-info-circle"></i> Stato Attuale</h4></div>
                <div class="card-body">
                    <div class="row mb-2">
                        <div class="col-6"><strong>Utente:</strong><br><span class="text-muted"><?php echo fullname($USER) . ' (' . $USER->username . ')'; ?></span></div>
                        <div class="col-6"><strong>ID:</strong><br><span class="text-muted"><?php echo $USER->id; ?></span></div>
                    </div>
                    <hr>
                    <div class="row mb-2">
                        <div class="col-6"><strong>Dipartimento:</strong><br><span class="badge badge-<?php echo $current_dept ? 'success' : 'secondary'; ?>"><?php echo $current_dept ?: 'Non impostato'; ?></span></div>
                        <div class="col-6"><strong>Posizione:</strong><br><span class="badge badge-<?php echo $current_pos ? 'success' : 'secondary'; ?>"><?php echo $current_pos ?: 'Non impostato'; ?></span></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header"><h4><i class="fa fa-cogs"></i> Azioni di Test</h4></div>
                <div class="card-body">
                    <button id="show-modal-btn" class="btn btn-primary btn-lg btn-block mb-3">
                        <i class="fa fa-window-restore"></i> <?php echo get_string('showmodal', 'local_whereareyou'); ?>
                    </button>
                    <a href="<?php echo $PAGE->url->out(false, ['action' => 'reset', 'sesskey' => sesskey()]); ?>" 
                       class="btn btn-warning btn-block mb-3" onclick="return confirm('Sei sicuro?');">
                        <i class="fa fa-refresh"></i> <?php echo get_string('resetdata', 'local_whereareyou'); ?>
                    </a>
                    <a href="<?php echo $PAGE->url->out(false, ['action' => 'clear_session', 'sesskey' => sesskey()]); ?>" 
                       class="btn btn-info btn-block">
                        <i class="fa fa-eraser"></i> Rimuovi Flag Sessione
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php echo $OUTPUT->footer(); ?>
