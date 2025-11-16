<?php
$patterns = loadJSONFile($data_dir . 'pattern.json');
?>
Why in capital letters? Because I already have made Eliza's chat bot data from 2009.<br><br>

<div class="card">
    <div class="card-header">Add New Pattern</div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="form_type" value="add_pattern">
            
            <div class="form-group">
                <label>Trigger Words (comma separated)</label>
                <input type="text" name="triggers" class="form-control" 
                       placeholder="HELLO,HI,HEY" required>
                <small>Enter words that will trigger this response, separated by commas</small>
            </div>
            
            <div class="form-group">
                <label>Responses (comma separated)</label>
                <textarea name="responses" class="form-control" rows="4" 
                          placeholder="Hello there!,Hi!,How can I help you?" required></textarea>
                <small>Enter possible responses, separated by commas. One will be chosen randomly.</small>
            </div>
            
            <button type="submit" class="btn btn-success">Add Pattern</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">Existing Patterns (Top patterns are prioritized)</div>
    <div class="card-body">
        <?php if (empty($patterns)): ?>
            <p>No patterns found.</p>
        <?php else: ?>
            <?php foreach ($patterns as $index => $pattern): ?>
                <div class="chat-detail">
                    <form method="post" style="margin-bottom: 15px;">
                        <input type="hidden" name="form_type" value="edit_pattern">
                        <input type="hidden" name="pattern_index" value="<?= $index ?>">
                        
                        <div class="form-group">
                            <label>Trigger Words</label>
                            <input type="text" name="triggers" class="form-control" 
                                   value="<?= htmlspecialchars(implode(', ', $pattern[0])) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Responses</label>
                            <textarea name="responses" class="form-control" rows="3"><?= htmlspecialchars(implode(', ', $pattern[1])) ?></textarea>
                        </div>
                        
                        <div class="action-buttons">
                            <button type="submit" class="btn btn-primary">Update</button>
                            
                            <?php if ($index > 0): ?>
                                <button type="button" class="btn btn-warning" 
                                        onclick="movePattern(<?= $index ?>, 'up')">Move Up</button>
                            <?php endif; ?>
                            
                            <?php if ($index < count($patterns) - 1): ?>
                                <button type="button" class="btn btn-warning" 
                                        onclick="movePattern(<?= $index ?>, 'down')">Move Down</button>
                            <?php endif; ?>
                            
                            <button type="submit" name="form_type" value="delete_pattern" 
                                    class="btn btn-danger" 
                                    onclick="return confirmDelete('Are you sure you want to delete this pattern?')">
                                Delete
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Hidden form for moving patterns -->
<form method="post" id="move_pattern_form" style="display: none;">
    <input type="hidden" name="form_type" value="move_pattern">
    <input type="hidden" name="pattern_index" id="pattern_index">
    <input type="hidden" name="direction" id="direction">
</form>
