<?php
require_once('../../config.php');

// Require admin login
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/local/whereareyou/test.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('test_page', 'local_whereareyou'));
$PAGE->set_heading(get_string('test_page', 'local_whereareyou'));
$PAGE->set_pagelayout('admin');

// Handle reset action
$reset = optional_param('reset', 0, PARAM_INT);
if ($reset && confirm_sesskey()) {
    // Reset user data
    $DB->delete_records('user_info_data', [
        'userid' => $USER->id,
        'fieldid' => $DB->get_field('user_info_field', 'id', ['shortname' => 'department'])
    ]);
    $DB->delete_records('user_info_data', [
        'userid' => $USER->id, 
        'fieldid' => $DB->get_field('user_info_field', 'id', ['shortname' => 'position'])
    ]);
    
    unset_user_preference('local_whereareyou_department');
    unset_user_preference('local_whereareyou_position');
    
    redirect($PAGE->url, get_string('data_reset', 'local_whereareyou'), null, \core\output\notification::NOTIFY_SUCCESS);
}

// Get current values
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

// Get available options
$department_options = [];
if ($department_field && !empty($department_field->param1)) {
    $options = explode("\n", $department_field->param1);
    foreach ($options as $option) {
        $option = trim($option);
        if (!empty($option)) {
            $department_options[] = ['value' => $option, 'text' => $option];
        }
    }
}

$position_options = [];
if ($position_field && !empty($position_field->param1)) {
    $options = explode("\n", $position_field->param1);
    foreach ($options as $option) {
        $option = trim($option);
        if (!empty($option)) {
            $position_options[] = ['value' => $option, 'text' => $option];
        }
    }
}

// Prepare template context for modal
$templatecontext = [
    'department_options' => $department_options,
    'position_options' => $position_options,
    'current_department' => $current_department,
    'current_position' => $current_position,
    'sesskey' => sesskey(),
    'wwwroot' => $CFG->wwwroot,
];

echo $OUTPUT->header();

?>
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fa fa-vial me-2"></i>
                        <?php echo get_string('test_modal', 'local_whereareyou'); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <p class="text-muted"><?php echo get_string('test_description', 'local_whereareyou'); ?></p>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                        <button type="button" class="btn btn-primary btn-lg" id="show-modal-btn">
                            <i class="fa fa-play me-2"></i>
                            <?php echo get_string('show_modal', 'local_whereareyou'); ?>
                        </button>
                        
                        <a href="<?php echo $PAGE->url->out(false, ['reset' => 1, 'sesskey' => sesskey()]); ?>" 
                           class="btn btn-warning" 
                           onclick="return confirm('<?php echo get_string('confirm_reset', 'local_whereareyou'); ?>')">
                            <i class="fa fa-undo me-2"></i>
                            <?php echo get_string('reset_data', 'local_whereareyou'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-info-circle me-2"></i>
                        <?php echo get_string('current_values', 'local_whereareyou'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5"><?php echo get_string('department_label', 'local_whereareyou'); ?></dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-secondary">
                                <?php echo $current_department ?: get_string('not_set', 'local_whereareyou'); ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-5"><?php echo get_string('position_label', 'local_whereareyou'); ?></dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-secondary">
                                <?php echo $current_position ?: get_string('not_set', 'local_whereareyou'); ?>
                            </span>
                        </dd>
                    </dl>
                    
                    <hr>
                    
                    <h6><?php echo get_string('available_options', 'local_whereareyou'); ?></h6>
                    
                    <p><strong><?php echo get_string('departments', 'local_whereareyou'); ?>:</strong><br>
                    <?php 
                    if (!empty($department_options)) {
                        foreach ($department_options as $opt) {
                            echo '<span class="badge bg-light text-dark me-1">' . $opt['text'] . '</span>';
                        }
                    } else {
                        echo '<em>' . get_string('no_options', 'local_whereareyou') . '</em>';
                    }
                    ?>
                    </p>
                    
                    <p><strong><?php echo get_string('positions', 'local_whereareyou'); ?>:</strong><br>
                    <?php 
                    if (!empty($position_options)) {
                        foreach ($position_options as $opt) {
                            echo '<span class="badge bg-light text-dark me-1">' . $opt['text'] . '</span>';
                        }
                    } else {
                        echo '<em>' . get_string('no_options', 'local_whereareyou') . '</em>';
                    }
                    ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fa fa-question-circle me-2"></i>
                        <?php echo get_string('instructions', 'local_whereareyou'); ?>
                    </h5>
                </div>
                <div class="card-body">
                    <ol>
                        <li><?php echo get_string('instruction_1', 'local_whereareyou'); ?></li>
                        <li><?php echo get_string('instruction_2', 'local_whereareyou'); ?></li>
                        <li><?php echo get_string('instruction_3', 'local_whereareyou'); ?></li>
                        <li><?php echo get_string('instruction_4', 'local_whereareyou'); ?></li>
                    </ol>
                    
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        <strong><?php echo get_string('note', 'local_whereareyou'); ?>:</strong> 
                        <?php echo get_string('test_note', 'local_whereareyou'); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const showModalBtn = document.getElementById('show-modal-btn');
    
    showModalBtn.addEventListener('click', function() {
        // Show loading state
        const originalText = showModalBtn.innerHTML;
        showModalBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Caricamento...';
        showModalBtn.disabled = true;
        
        // Import and initialize modal
        require(['local_whereareyou/modal'], function(modal) {
            try {
                const config = <?php echo json_encode($templatecontext); ?>;
                modal.init(config);
            } catch (error) {
                console.error('Error initializing modal:', error);
                alert('Errore nel caricare la modale. Controlla la console per dettagli.');
            } finally {
                // Restore button state
                showModalBtn.innerHTML = originalText;
                showModalBtn.disabled = false;
            }
        }, function(error) {
            // RequireJS error callback
            console.error('RequireJS error:', error);
            alert('Errore nel caricare il modulo JavaScript. Assicurati che il plugin sia installato correttamente.');
            
            // Restore button state
            showModalBtn.innerHTML = originalText;
            showModalBtn.disabled = false;
        });
    });
});
</script>

<?php
echo $OUTPUT->footer();