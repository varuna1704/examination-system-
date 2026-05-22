<?php
session_start();
include 'config.php';
require_once 'lib/security.php';

$search_key = isset($_GET['key']) ? trim($_GET['key']) : '';
$cert = null;
$error = '';

if ($search_key !== '') {
    $stmt = $conn->prepare("
        SELECT ea.id as attempt_id, ea.percentage, ea.level, ea.started_at, ea.submitted_at, ea.exam_mode,
               u.f_name, u.m_name, u.l_name, s.name as subject_name
        FROM exam_attempts ea
        JOIN users u ON u.id = ea.user_id
        JOIN subjects s ON s.id = ea.subject_id
        WHERE ea.verification_key = ? AND ea.percentage >= 50.00
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("s", $search_key);
        $stmt->execute();
        $cert = $stmt->get_result()->fetch_assoc();
        if (!$cert) {
            $error = "No active certified record found matching this verification key. Ensure the certificate is authentic and has a passing mark (>= 50%).";
        }
    } else {
        $error = "System database query error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification Registry | ExamPortal Pro</title>
    <link rel="stylesheet" href="modern-style.css">
    <style>
        .verify-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            padding: 3rem;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 650px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            text-align: center;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .cert-seal {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #fbbf24 0%, #d97706 100%);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            box-shadow: 0 8px 16px rgba(217, 119, 6, 0.3);
            border: 4px solid #fff;
            margin-bottom: 1.5rem;
            position: relative;
        }
        .cert-seal::after {
            content: 'VERIFIED';
            position: absolute;
            bottom: -8px;
            background: #1e3a8a;
            color: #fff;
            font-size: 0.6rem;
            font-weight: 800;
            padding: 2px 8px;
            border-radius: 10px;
            letter-spacing: 1px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .cert-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            text-align: left;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px dashed var(--gray-300);
        }
        .cert-field {
            font-size: 0.85rem;
            color: var(--gray-600);
            font-weight: 500;
        }
        .cert-val {
            font-size: 1rem;
            color: var(--gray-900);
            font-weight: 700;
            margin-top: 0.25rem;
        }
        .full-width {
            grid-column: span 2;
        }
    </style>
</head>
<body>
    <?php include("modern_header.php"); ?>
    
    <div class="container flex-center">
        <div class="verify-card">
            <h1 style="font-size: 1.8rem; margin-bottom: 0.5rem; background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                Secure Verification Registry
            </h1>
            <p class="text-muted" style="margin-bottom: 2rem;">Verify official academic credentials and certification badges issued by the ExamPortal Pro engine.</p>
            
            <form action="" method="get" style="margin-bottom: 2rem;">
                <div style="display: flex; gap: 0.5rem; box-shadow: var(--shadow); border-radius: 8px; overflow: hidden; background: white; border: 2px solid var(--primary);">
                    <input type="text" name="key" value="<?php echo e($search_key); ?>" placeholder="Enter Certificate Verification ID (e.g. CERT-EPP-...)" required style="border: none; padding: 0.85rem 1rem; flex-grow: 1; outline: none; font-family: monospace; font-size: 0.9rem;">
                    <button type="submit" class="btn btn-inline" style="width: auto; border-radius: 0; padding: 0.85rem 1.5rem; margin: 0;">Verify Now</button>
                </div>
            </form>

            <?php if ($cert): 
                $fullName = trim($cert['f_name'] . ' ' . $cert['m_name'] . ' ' . $cert['l_name']);
                if (empty($fullName)) $fullName = 'N/A';
            ?>
                <div style="border: 2px solid #fbbf24; border-radius: 12px; padding: 2rem; background: linear-gradient(180deg, rgba(254, 243, 199, 0.2) 0%, rgba(255, 255, 255, 1) 100%); margin-top: 1rem;">
                    <div class="cert-seal">🎓</div>
                    
                    <h2 style="font-size: 1.5rem; color: #1e3b8a; margin-bottom: 0.25rem;">Credential Validated</h2>
                    <span style="font-size: 0.8rem; font-family: monospace; color: var(--primary); background: #e0f2fe; padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: 600;"><?php echo e($search_key); ?></span>
                    
                    <div class="cert-detail-grid">
                        <div class="full-width">
                            <div class="cert-field">Certified Professional</div>
                            <div class="cert-val" style="font-size: 1.25rem; color: #1e3a8a;"><?php echo e($fullName); ?></div>
                        </div>
                        <div class="full-width">
                            <div class="cert-field">Certification Track</div>
                            <div class="cert-val"><?php echo e($cert['subject_name']); ?></div>
                        </div>
                        <div>
                            <div class="cert-field">Difficulty Level</div>
                            <div class="cert-val" style="color: var(--secondary);"><?php echo e($cert['level']); ?></div>
                        </div>
                        <div>
                            <div class="cert-field">Examination Score</div>
                            <div class="cert-val" style="color: #10b981;"><?php echo e(round($cert['percentage'], 2)); ?>% Accuracy</div>
                        </div>
                        <div class="full-width">
                            <div class="cert-field">Date of Award</div>
                            <div class="cert-val"><?php echo date("F j, Y, g:i a", strtotime($cert['submitted_at'])); ?></div>
                        </div>
                    </div>
                </div>
            <?php elseif ($error !== ''): ?>
                <div class="alert alert-error" style="text-align: left; margin-top: 1rem;">
                    <span style="font-size: 1.5rem;">❌</span>
                    <div><strong>Verification Failed!</strong><br><?php echo e($error); ?></div>
                </div>
            <?php elseif ($search_key !== ''): ?>
                <div class="alert alert-error" style="text-align: left; margin-top: 1rem;">
                    <span style="font-size: 1.5rem;">❌</span>
                    <div><strong>Invalid Request!</strong> Please type a search key above.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
