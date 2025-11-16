<?php
$companyData = loadJSONFile($data_dir . 'company.txt');
?>

<div class="card">
    <div class="card-header">Edit Company Information</div>
    <div class="card-body">
        <form method="post">
            <input type="hidden" name="form_type" value="edit_company">
            
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="name" class="form-control" 
                       value="<?= htmlspecialchars($companyData['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($companyData['address'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control" 
                       value="<?= htmlspecialchars($companyData['phone'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($companyData['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>WhatsApp</label>
                <input type="text" name="wa" class="form-control" 
                       value="<?= htmlspecialchars($companyData['wa'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Telegram</label>
                <input type="text" name="telegram" class="form-control" 
                       value="<?= htmlspecialchars($companyData['telegram'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>How to Buy</label>
                <textarea name="cara_beli" class="form-control" rows="4"><?= htmlspecialchars($companyData['cara_beli'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Security Policy</label>
                <textarea name="security_policy" class="form-control" rows="4"><?= htmlspecialchars($companyData['security_policy'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Warranty Policy</label>
                <textarea name="warranty_policy" class="form-control" rows="4"><?= htmlspecialchars($companyData['warranty_policy'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-success">Update Company Information</button>
        </form>
    </div>
</div>