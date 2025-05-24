<?php
require_once('../../config.php');

// Require admin login
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/local/whereareyou/debug.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('WhereAreYou Debug');
$PAGE->set_heading('WhereAreYou Debug');
$PAGE->set_pagelayout('admin');

// Handle force show action
$forceshow = optional_param('forceshow', 0, PARAM_INT);
if ($forceshow && confirm_sesskey()) {
    // Reset the last shown preference to force modal display
    unset_user_preference('local_whereareyou_last_shown');
    redirect(new moodle_url('/'), 'Preferenza resettata. La modale dovrebbe apparire nella prossima pagina.', null, \core\output\notification::NOTIFY_SUCCESS);
}

// Get current user data
$department_field = $DB->get_record('user_info_field', ['shortname' => 'department']);
$position_field = $DB->get_record('user_info_field', ['shortname' => 'position']);

$current_department = '';
$current_position = '';

if ($department_field) {
    $data = $DB->get_record('user_info_data', ['userid' => $USER->id, 'fieldid' => $department_field->id]);
    $current_department = $data ? $data->data : '';
}

if ($position_field) {
    $data = $DB->get_record('user_info_data', ['userid' => $USER->id, 'fieldid' => $position_field->id]);
    $current_position = $data ? $data->data : '';
}

// Get preferences
$last_shown = get_user_preferences('local_whereareyou_last_shown', 0, $USER->id);
$last_shown_date = $last_shown ? date('Y-m-d H:i:s', $last_shown) : 'Mai';

// Check if modal should be shown
$should_show = false;
$reason = '';

if (empty($department_field) || empty($position_field)) {
    $reason = 'Campi profilo non configurati';
} elseif (empty($department_field->param1) || empty($position_field->param1)) {
    $reason = 'Opzioni dei campi profilo non configurate';
} elseif (!empty($current_department) && !empty($current_position)) {
    $reason = 'Entrambi i campi sono già impostati';
} else {
    $today = date('Y-m-d');
    $last_shown_date_only = $last_shown ? date('Y-m-d', $last_shown) : '';
    
    if ($last_shown_date_only === $today && (!empty($current_department) || !empty($current_position))) {
        $reason = 'Già mostrata oggi e dati parzialmente impostati';
    } else {
        $should_show = true;
        $reason = 'Dovrebbe essere mostrata';
    }
}

echo $OUTPUT->header();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Stato Attuale</h4>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <tr>
                            <th>Utente</th>
                            <td><?php echo fullname($USER) . ' (ID: ' . $USER->id . ')'; ?></td>
                        </tr>
                        <tr>
                            <th>Dipartimento Attuale</th>
                            <td><?php echo $current_department ?: '<em>Non impostato</em>'; ?></td>
                        </tr>
                        <tr>
                            <th>Posizione Attuale</th>
                            <td><?php echo $current_position ?: '<em>Non impostata</em>'; ?></td>
                        </tr>
                        <tr>
                            <th>Ultima Visualizzazione</th>
                            <td><?php echo $last_shown_date; ?></td>
                        </tr>
                        <tr>
                            <th>Dovrebbe Mostrarsi?</th>
                            <td>
                                <span class="badge bg-<?php echo $should_show ? 'success' : 'danger'; ?>">
                                    <?php echo $should_show ? 'SÌ' : 'NO'; ?>
                                </span>
                                <br><small><?php echo $reason; ?></small>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4>Configurazione Campi</h4>
                </div>
                <div class="card-body">
                    <h6>Campo Dipartimento:</h6>
                    <?php if ($department_field): ?>
                        <ul>
                            <li>ID: <?php echo $department_field->id; ?></li>
                            <li>Nome: <?php echo $department_field->name; ?></li>
                            <li>Opzioni: 
                                <?php 
                                if ($department_field->param1) {
                                    $options = explode("\n", $department_field->param1);
                                    echo '<br>' . implode('<br>', array_map('trim', $options));
                                } else {
                                    echo '<em>Nessuna opzione configurata</em>';
                                }
                                ?>
                            </li>
                        </ul>
                    <?php else: ?>
                        <p class="text-danger">Campo non trovato!</p>
                    <?php endif; ?>
                    
                    <h6>Campo Posizione:</h6>
                    <?php if ($position_field): ?>
                        <ul>
                            <li>ID: <?php echo $position_field->id; ?></li>
                            <li>Nome: <?php echo $position_field->name; ?></li>
                            <li>Opzioni: 
                                <?php 
                                if ($position_field->param1) {
                                    $options = explode("\n", $position_field->param1);
                                    echo '<br>' . implode('<br>', array_map('trim', $options));
                                } else {
                                    echo '<em>Nessuna opzione configurata</em>';
                                }
                                ?>
                            </li>
                        </ul>
                    <?php else: ?>
                        <p class="text-danger">Campo non trovato!</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4>Azioni di Test</h4>
                </div>
                <div class="card-body">
                    <a href="<?php echo $PAGE->url->out(false, ['forceshow' => 1, 'sesskey' => sesskey()]); ?>" 
                       class="btn btn-primary">
                        Forza Visualizzazione Modale (vai alla homepage)
                    </a>
                    
                    <a href="test.php" class="btn btn-secondary">
                        Vai alla Pagina di Test
                    </a>
                    
                    <button type="button" class="btn btn-info" onclick="checkConsole()">
                        Controlla Console JavaScript
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function checkConsole() {
    console.log('=== WhereAreYou Debug Info ===');
    console.log('Current URL:', window.location.href);
    console.log('Page layout:', '<?php echo $PAGE->pagelayout; ?>');
    console.log('User logged in:', <?php echo isloggedin() ? 'true' : 'false'; ?>);
    console.log('Is guest:', <?php echo isguestuser() ? 'true' : 'false'; ?>);
    console.log('AJAX_SCRIPT defined:', typeof AJAX_SCRIPT !== 'undefined');
    console.log('RequireJS available:', typeof require !== 'undefined');
    console.log('WhereAreYou config:', window.whereAreYouConfig || 'Not set');
    console.log('WhereAreYou initialized:', window.whereAreYouInitialized || false);
    console.log('=== End Debug Info ===');
    
    alert('Debug info scritto nella console. Apri gli strumenti sviluppatore per vederlo.');
}
</script>

<?php
echo $OUTPUT->footer();