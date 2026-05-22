<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/security.php';
require_login();

$attemptId = (int)($_GET['attempt_id'] ?? 0);
if ($attemptId <= 0) {
    http_response_code(400);
    exit('Invalid attempt ID.');
}

// Ownership check & fetch attempt details
$attemptStmt = $conn->prepare("
    SELECT ea.*, s.name AS subject_name 
    FROM exam_attempts ea
    JOIN subjects s ON ea.subject_id = s.id
    WHERE ea.id = ? AND ea.user_id = ?
");
$attemptStmt->bind_param("ii", $attemptId, $_SESSION['user_id']);
$attemptStmt->execute();
$attempt = $attemptStmt->get_result()->fetch_assoc();

if (!$attempt) {
    http_response_code(403);
    exit('Access denied or attempt not found.');
}

// Verify it's official and passing
if ($attempt['exam_mode'] !== 'official') {
    exit('Certificates are only generated for official exams.');
}
if ($attempt['percentage'] < 50.00) {
    exit('You did not achieve the required passing score (50%) to receive a certificate.');
}

// Fetch user registration details
$userStmt = $conn->prepare("SELECT f_name, m_name, l_name, created_at FROM users WHERE id = ?");
$userStmt->bind_param("i", $_SESSION['user_id']);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();
if (!$user) {
    exit('User details not found.');
}

// Candidate full name
$fullname = trim(($user['f_name'] ?? '') . ' ' . ($user['m_name'] ?? '') . ' ' . ($user['l_name'] ?? ''));
$fullname = preg_replace('/\s+/', ' ', $fullname);
if (empty($fullname)) {
    $fullname = $_SESSION['u_name'];
}

$registrationDate = date("F j, Y, g:i a", strtotime($user['created_at']));
$completionDate = date("F j, Y", strtotime($attempt['submitted_at']));
$certVerificationCode = 'CERT-EPP-' . $attemptId . '-' . strtoupper(substr(md5($attemptId . 'EXAMPORTAL_SECURE_SALT'), 0, 8));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate | <?php echo htmlspecialchars($fullname); ?></title>
    <!-- Import elegant serif & cursive fonts for certificate styling -->
    <link href="https://fonts.googleapis.com/css2?family=Alex+Brush&family=Cinzel:wght@500;700;800&family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --gold: #d4af37;
            --dark-gold: #aa7c11;
            --slate-800: #1e293b;
            --slate-600: #475569;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Montserrat', sans-serif;
            background: #f1f5f9;
            color: var(--slate-800);
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        .no-print-bar {
            width: 100%;
            max-width: 1050px;
            background: white;
            padding: 1rem 2rem;
            margin: 1.5rem 0;
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-print {
            background: var(--primary);
            color: white;
            border: none;
        }
        .btn-print:hover { background: #4f46e5; }
        .btn-back {
            background: #e2e8f0;
            color: var(--slate-600);
            border: 1px solid #cbd5e1;
        }
        .btn-back:hover { background: #cbd5e1; }

        /* Certificate Container (Landscape A4 ratio: 297mm x 210mm ~ 1.414 ratio) */
        .certificate-wrapper {
            background: white;
            width: 1050px;
            height: 742px;
            padding: 2.5rem;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            position: relative;
            overflow: hidden;
            border-radius: 4px;
            margin-bottom: 2rem;
        }

        /* Border Details */
        .certificate-border-outer {
            width: 100%;
            height: 100%;
            border: 18px solid var(--slate-800);
            padding: 4px;
            position: relative;
        }

        .certificate-border-inner {
            width: 100%;
            height: 100%;
            border: 3px double var(--gold);
            padding: 2.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        /* Decorative Corner Brackets */
        .corner-deco {
            position: absolute;
            width: 40px;
            height: 40px;
            border: 3px solid var(--gold);
        }
        .top-left     { top: 5px; left: 5px; border-right: none; border-bottom: none; }
        .top-right    { top: 5px; right: 5px; border-left: none; border-bottom: none; }
        .bottom-left  { bottom: 5px; left: 5px; border-right: none; border-top: none; }
        .bottom-right { bottom: 5px; right: 5px; border-left: none; border-top: none; }

        /* Content Layout */
        .cert-header {
            font-family: 'Cinzel', serif;
            font-weight: 700;
            color: var(--slate-800);
            text-align: center;
            letter-spacing: 4px;
            margin-top: 1rem;
        }
        .cert-header h1 {
            font-size: 2.4rem;
            line-height: 1.1;
        }
        .cert-header h2 {
            font-size: 1rem;
            color: var(--dark-gold);
            margin-top: 0.5rem;
            font-weight: 800;
            letter-spacing: 6px;
        }

        .cert-presentation {
            text-align: center;
            font-size: 1.1rem;
            font-style: italic;
            color: var(--slate-600);
            margin-top: 1rem;
        }

        .cert-name {
            font-family: 'Alex Brush', cursive;
            font-size: 4rem;
            color: var(--slate-800);
            text-align: center;
            margin: 1rem 0;
            text-shadow: 1px 1px 1px rgba(0,0,0,0.15);
            border-bottom: 2px solid #e2e8f0;
            width: 80%;
            padding-bottom: 0.5rem;
        }

        .cert-details {
            text-align: center;
            max-width: 85%;
            font-size: 1.15rem;
            line-height: 1.6;
            color: var(--slate-600);
        }
        .cert-details strong {
            color: var(--slate-800);
        }

        .cert-meta-container {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-top: 1rem;
            padding: 0 1rem;
        }

        .cert-meta-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 25%;
        }
        .cert-line {
            width: 100%;
            border-top: 2px solid var(--slate-800);
            margin-bottom: 0.5rem;
        }
        .cert-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--slate-600);
            font-weight: 600;
            text-align: center;
        }
        .cert-val {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--slate-800);
            margin-bottom: 0.2rem;
        }

        /* Gold Stamp Design */
        .gold-seal {
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, #fce074 0%, #d4af37 60%, #b28d17 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2), inset 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            border: 2px dashed #aa7c11;
        }
        .gold-seal-inner {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            border: 2px solid #8e6508;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #5b4003;
            text-align: center;
        }
        .gold-seal-text-top {
            font-size: 0.45rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1px;
        }
        .gold-seal-star {
            font-size: 1rem;
            color: #5b4003;
            margin: 0.15rem 0;
        }
        .gold-seal-text-bottom {
            font-size: 0.5rem;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        /* Seal Ribbons */
        .seal-ribbon-container {
            position: absolute;
            z-index: -1;
            top: 60px;
        }
        .ribbon-1, .ribbon-2 {
            position: absolute;
            width: 25px;
            height: 60px;
            background: #b91c1c; /* Crimson ribbons */
            box-shadow: 2px 2px 5px rgba(0,0,0,0.15);
        }
        .ribbon-1 {
            transform: rotate(25deg);
            left: -18px;
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 80%, 0% 100%);
        }
        .ribbon-2 {
            transform: rotate(-5deg);
            left: 2px;
            clip-path: polygon(0% 0%, 100% 0%, 100% 100%, 50% 80%, 0% 100%);
        }

        .cert-footer-info {
            width: 100%;
            display: flex;
            justify-content: space-between;
            padding: 0 1rem;
            font-size: 0.75rem;
            color: var(--slate-600);
            border-top: 1px solid #f1f5f9;
            padding-top: 0.75rem;
        }

        /* Watermark Background */
        .watermark {
            position: absolute;
            font-family: 'Cinzel', serif;
            font-size: 7rem;
            font-weight: 800;
            color: rgba(226, 232, 240, 0.28);
            transform: rotate(-30deg);
            z-index: 0;
            pointer-events: none;
            white-space: nowrap;
            letter-spacing: 8px;
        }

        /* Print Layout Adjustments */
        @media print {
            @page {
                size: A4 landscape;
                margin: 0;
            }
            body {
                background: white;
                min-height: auto;
            }
            .no-print-bar {
                display: none !important;
            }
            .certificate-wrapper {
                box-shadow: none !important;
                margin: 0 !important;
                border-radius: 0 !important;
                width: 100% !important;
                height: 100vh !important;
                padding: 1.5cm !important;
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>

    <!-- Web View Navigation & Action Bar -->
    <div class="no-print-bar">
        <div>
            <h3 style="font-weight: 700; color: var(--slate-800);">Your Certification Certificate</h3>
            <p style="font-size: 0.85rem; color: var(--slate-600); margin-top: 0.1rem;">Print or download this certificate for your records.</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="history.php" class="btn-action btn-back">&larr; Back to History</a>
            <button onclick="window.print()" class="btn-action btn-print">🖨️ Print Certificate</button>
        </div>
    </div>

    <!-- Certificate Card -->
    <div class="certificate-wrapper">
        <div class="watermark">EXAMPORTAL PRO</div>
        
        <div class="certificate-border-outer">
            <!-- Decorative Corners -->
            <div class="corner-deco top-left"></div>
            <div class="corner-deco top-right"></div>
            <div class="corner-deco bottom-left"></div>
            <div class="corner-deco bottom-right"></div>
            
            <div class="certificate-border-inner">
                
                <!-- Certification Header -->
                <div class="cert-header">
                    <h1>EXAMPORTAL PRO</h1>
                    <h2>Certificate of Achievement</h2>
                </div>

                <!-- Presentation text -->
                <p class="cert-presentation">This official document is proudly presented to</p>

                <!-- Candidate Name -->
                <div class="cert-name">
                    <?php echo htmlspecialchars($fullname); ?>
                </div>

                <!-- Exam details description -->
                <div class="cert-details">
                    for demonstrating technical proficiency and successfully passing the certification examination in 
                    <strong><?php echo htmlspecialchars($attempt['subject_name']); ?></strong> 
                    at the <strong><?php echo htmlspecialchars($attempt['level']); ?> Level</strong> 
                    with a score of <strong><?php echo (int)$attempt['score']; ?>/<?php echo (int)$attempt['total_questions']; ?></strong> 
                    (<strong><?php echo round($attempt['percentage'], 1); ?>%</strong>).
                </div>

                <!-- Signatures & Seal Container -->
                <div class="cert-meta-container">
                    
                    <!-- Date Issued -->
                    <div class="cert-meta-item">
                        <span class="cert-val"><?php echo $completionDate; ?></span>
                        <div class="cert-line"></div>
                        <span class="cert-label">Date Issued</span>
                    </div>

                    <!-- Seal -->
                    <div style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 110px;">
                        <div class="gold-seal">
                            <div class="gold-seal-inner">
                                <span class="gold-seal-text-top">Verified</span>
                                <span class="gold-seal-star">★</span>
                                <span class="gold-seal-text-bottom">Official Seal</span>
                            </div>
                        </div>
                        <div class="seal-ribbon-container">
                            <div class="ribbon-1"></div>
                            <div class="ribbon-2"></div>
                        </div>
                    </div>

                    <!-- Signature line -->
                    <div class="cert-meta-item">
                        <span class="cert-val" style="font-family: 'Alex Brush', cursive; font-size: 1.8rem; line-height: 0.8; font-weight: normal; color: #1e3a8a;">Varuna Nikam</span>
                        <div class="cert-line" style="border-top-style: solid;"></div>
                        <span class="cert-val" style="font-size: 0.9rem; font-weight: 700; color: var(--slate-800); margin-bottom: 0.1rem;">Varuna Nikam</span>
                        <span class="cert-label">Certification Director</span>
                    </div>

                </div>

                <!-- Footer Info (Verification ID & Candidate Registration Date) -->
                <div class="cert-footer-info">
                    <div>
                        Candidate Reg. Time: <strong><?php echo htmlspecialchars($registrationDate); ?></strong>
                    </div>
                    <div>
                        Verification ID: <strong><?php echo htmlspecialchars($certVerificationCode); ?></strong>
                    </div>
                </div>

            </div>
        </div>
    </div>

</body>
</html>
