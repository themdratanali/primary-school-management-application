<?php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/csrf.php';
include '../config/config.php';

if (!isset($_SESSION['admin'])) {
    header('Location: ../admin/login.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    ams_csrf_verify_post();
    $name = trim($_POST['name']);
    $designation = trim($_POST['designation'] ?? '');
    $mother_name = trim($_POST['mother_name'] ?? '');
    $father_name = trim($_POST['father_name'] ?? '');
    $gender = $_POST['gender'] ?? null;
    $dob = $_POST['dob'] ?? null;
    $blood_group = trim($_POST['blood_group'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $nationality = trim($_POST['nationality'] ?? '');
    $nid = trim($_POST['nid'] ?? '');
    $present_address = trim($_POST['present_address'] ?? '');
    $permanent_address = trim($_POST['permanent_address'] ?? '');
    
    $education = isset($_POST['education']) ? $_POST['education'] : [];
    $institute = isset($_POST['institute']) ? $_POST['institute'] : [];
    $result_array = isset($_POST['result']) ? $_POST['result'] : [];
    
    // Combine education, institute, and result into structured format
    $educationData = [];
    if (!empty($education) && is_array($education)) {
        for ($i = 0; $i < count($education); $i++) {
            if (!empty($education[$i])) {
                $educationData[] = [
                    'education' => $education[$i],
                    'institute' => isset($institute[$i]) ? $institute[$i] : '',
                    'result' => isset($result_array[$i]) ? $result_array[$i] : ''
                ];
            }
        }
    }
    $educationJson = json_encode($educationData, JSON_UNESCAPED_UNICODE);
    $experience = trim($_POST['experience'] ?? '');
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $login_password = $_POST['login_password'] ?? '';
    $subject_ids = $_POST['subject_ids'] ?? [];

    $photo = null;

    if (empty($name) || empty($phone) || empty($email) || empty($gender) || empty($login_password)) {
        $message = "Please fill all required fields (Name, Gender, Phone, Email, Password).";
    } else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
            $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_ext)) {
                if ($_FILES['photo']['size'] <= 2 * 1024 * 1024) {
                    if (!is_dir('../uploads/teachers')) {
                        mkdir('../uploads/teachers', 0777, true);
                    }
                    $photo = '../uploads/teachers/' . uniqid('teacher_', true) . '.' . $file_ext;
                    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo)) {
                        $message = "Failed to upload photo.";
                    }
                } else {
                    $message = "Photo must be under 2MB.";
                }
            } else {
                $message = "Invalid photo format. Allowed: jpg, jpeg, png, gif.";
            }
        }
    }

    if (empty($message)) {
        $hashed_password = password_hash($login_password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO teachers (
            name, designation, mother_name, father_name, gender, dob, blood_group, religion,
            nationality, nid, present_address, permanent_address, education, experience,
            phone, email, login_password, plain_password, photo
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            $message = "Prepare failed: " . $conn->error;
        } else {
            
            $stmt->bind_param(
                "sssssssssssssssssss",
                $name,
                $designation,
                $mother_name,
                $father_name,
                $gender,
                $dob,
                $blood_group,
                $religion,
                $nationality,
                $nid,
                $present_address,
                $permanent_address,
                $educationJson,
                $experience,
                $phone,
                $email,
                $hashed_password,
                $login_password,
                $photo
            );
            if ($stmt->execute()) {
                $teacher_id = $conn->insert_id;
                
                // Add subjects for the teacher
                if (!empty($subject_ids) && is_array($subject_ids)) {
                    $subject_stmt = $conn->prepare("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)");
                    foreach ($subject_ids as $subject_id) {
                        $subject_id = intval($subject_id);
                        $subject_stmt->bind_param("ii", $teacher_id, $subject_id);
                        $subject_stmt->execute();
                    }
                    $subject_stmt->close();
                }
                
                $message = "Teacher added successfully with login credentials!";
            } else {
                $message = "Execute failed: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Add Teacher - Apex Model School</title>
    <link rel="shortcut icon" type="image/jpg" href="../assets/img/এ্যাপেক্স মডেল স্কুল.png"/>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/css/add_teacher.css">
</head>

<body>
    <div class="container">
        <h2>Teacher Form</h2>
        
        <?php if (!empty($message)): ?>
            <div class="alert <?= strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data" novalidate>
            <?= ams_csrf_field() ?>
            <fieldset>
                <legend>Personal Information</legend>
                <div class="row">
                    <div>
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>
                    </div>
                    <div>
                        <label for="gender">Gender *</label>
                        <select id="gender" name="gender" required>
                            <option value="">Select Gender</option>
                            <option value="Male" <?= (isset($gender) && $gender==='Male')?'selected':'' ?>>Male</option>
                            <option value="Female" <?= (isset($gender) && $gender==='Female')?'selected':'' ?>>Female</option>
                            <option value="Other" <?= (isset($gender) && $gender==='Other')?'selected':'' ?>>Other</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="mother_name">Mother's Name</label>
                        <input type="text" id="mother_name" name="mother_name" value="<?= isset($mother_name) ? htmlspecialchars($mother_name) : '' ?>">
                    </div>
                    <div>
                        <label for="father_name">Father's Name</label>
                        <input type="text" id="father_name" name="father_name" value="<?= isset($father_name) ? htmlspecialchars($father_name) : '' ?>">
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="dob">Date of Birth</label>
                        <input type="date" id="dob" name="dob" value="<?= isset($dob) ? htmlspecialchars($dob) : '' ?>">
                    </div>
                    <div>
                        <label for="blood_group">Blood Group</label>
                        <input type="text" id="blood_group" name="blood_group" value="<?= isset($blood_group) ? htmlspecialchars($blood_group) : '' ?>">
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="religion">Religion</label>
                        <select id="religion" name="religion">
                            <option value="">Select Religion</option>
                            <option value="Islam" <?= (isset($religion) && $religion==='Islam')?'selected':'' ?>>Islam</option>
                            <option value="Hinduism" <?= (isset($religion) && $religion==='Hinduism')?'selected':'' ?>>Hinduism</option>
                            <option value="Christianity" <?= (isset($religion) && $religion==='Christianity')?'selected':'' ?>>Christianity</option>
                            <option value="Buddhism" <?= (isset($religion) && $religion==='Buddhism')?'selected':'' ?>>Buddhism</option>
                            <option value="Other" <?= (isset($religion) && $religion==='Other')?'selected':'' ?>>Other</option>
                        </select>
                    </div>
                    <div>
                        <label for="nationality">Nationality</label>
                        <input type="text" id="nationality" name="nationality" value="<?= isset($nationality) ? htmlspecialchars($nationality) : '' ?>">
                    </div>
                </div>

                <div class="row">
                    <div>
                        <label for="nid">NID</label>
                        <input type="text" id="nid" name="nid" value="<?= isset($nid) ? htmlspecialchars($nid) : '' ?>">
                    </div>
                    <div>
                        <label for="designation">Designation *</label>
                        <select id="designation" name="designation" required>
                            <option value="">Select Designation</option>
                            <option value="Principal" <?= (isset($designation) && $designation==='Principal')?'selected':'' ?>>Principal</option>
                            <option value="Assistant Principal" <?= (isset($designation) && $designation==='Assistant Principal')?'selected':'' ?>>Assistant Principal</option>
                            <option value="Acting Principal" <?= (isset($designation) && $designation==='Acting Principal')?'selected':'' ?>>Acting Principal</option>
                            <option value="Principal (Charge)" <?= (isset($designation) && $designation==='Principal (Charge)')?'selected':'' ?>>Principal (Charge)</option>
                            <option value="Assistant Teacher" <?= (isset($designation) && $designation==='Assistant Teacher')?'selected':'' ?>>Assistant Teacher</option>
                            <option value="Senior Teacher" <?= (isset($designation) && $designation==='Senior Teacher')?'selected':'' ?>>Senior Teacher</option>
                            <option value="Junior Teacher" <?= (isset($designation) && $designation==='Junior Teacher')?'selected':'' ?>>Junior Teacher</option>
                            <option value="Pre-Primary Teacher" <?= (isset($designation) && $designation==='Pre-Primary Teacher')?'selected':'' ?>>Pre-Primary Teacher</option>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Address</legend>
                <label for="present_address">Present Address</label>
                <textarea id="present_address" name="present_address"><?= isset($present_address) ? htmlspecialchars($present_address) : '' ?></textarea>

                <label for="permanent_address">Permanent Address</label>
                <textarea id="permanent_address" name="permanent_address"><?= isset($permanent_address) ? htmlspecialchars($permanent_address) : '' ?></textarea>
            </fieldset>

            <fieldset>
                <legend>Professional</legend>
                
                <div id="education-entries">
                    <div class="education-entry">
                        <label>Education *</label>
                        <select name="education[]" class="education-select">
                            <option value="">Select Education</option>
                            <option value="SSC">SSC</option>
                            <option value="HSC">HSC</option>
                            <option value="Diploma">Diploma</option>
                            <option value="BSc">BSc</option>
                            <option value="MSc">MSc</option>
                            <option value="BA">BA</option>
                            <option value="MA">MA</option>
                            <option value="BSS">BSS</option>
                            <option value="MSS">MSS</option>
                            <option value="BA(Hons)">BA(Hons)</option>
                            <option value="MA(Pro)">MA(Pro)</option>
                            <option value="BBA">BBA</option>
                            <option value="MBA">MBA</option>
                            <option value="MBBS">MBBS</option>
                            <option value="BSc Engineering">BSc Engineering</option>
                            <option value="MSc Engineering">MSc Engineering</option>
                            <option value="Bachelor">Bachelor</option>
                            <option value="Master">Master</option>
                            <option value="PhD">PhD</option>
                        </select>
                        
                        <label>Education Institute</label>
                        <input type="text" name="institute[]" placeholder="Enter institute name">
                        
                        <label>Result</label>
                        <input type="text" name="result[]" placeholder="Enter result">
                        
                        <button type="button" class="remove-education-btn" style="display:none;">&times;</button>
                    </div>
                </div>
                
                <button type="button" id="add-education-btn" class="add-btn" onclick="addEducation()">+ Add More Education</button>
            </fieldset>

            <fieldset>
                <legend>Experience</legend>
                <label for="experience">Experience Details</label>
                <textarea id="experience" name="experience"><?= isset($experience) ? htmlspecialchars($experience) : '' ?></textarea>
            </fieldset>

            <fieldset>
                <legend>Contact & Login</legend>
                <label for="phone">Phone *</label>
                <input type="text" id="phone" name="phone" value="<?= isset($phone) ? htmlspecialchars($phone) : '' ?>" required>

                <label for="email">Email *</label>
                <input type="email" id="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>

                <label for="login_password">Login Password *</label>
                <div class="password-wrapper">
                    <input type="password" id="login_password" name="login_password" minlength="6" required>
                    <button type="button" id="togglePassword" class="password-toggle" aria-label="Show password">
                        <!-- eye icon -->
                        <svg id="teacherIconShow" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                            <path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="3" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <!-- eye-off icon (hidden) -->
                        <svg id="teacherIconHide" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="display:none;">
                            <path d="M3 3l18 18" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M10.58 10.58a3 3 0 004.24 4.24" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M2 12s4-7 10-7c2.05 0 3.92.5 5.6 1.36" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <small>Minimum 6 characters</small>
            </fieldset>

            <fieldset>
                <legend>Subjects</legend>
                <label for="subject_ids">Subjects (Select one or multiple)</label>
                
                <div class="custom-multiselect">
                    <div class="multiselect-selected" id="multiselect-selected">
                        <span class="placeholder" style="background-color: white;">Click to select subjects...</span>
                        <span class="dropdown-arrow">&#9662;</span>
                    </div>
                    <div class="multiselect-dropdown" id="multiselect-dropdown">
                        <div class="search-box">
                            <input type="text" id="subject-search" placeholder="Search subjects...">
                        </div>
                        <div class="options-container" id="options-container">
                            <?php 
                            $subjects = $conn->query("SELECT id, name FROM subjects ORDER BY name");
                            while ($row = $subjects->fetch_assoc()): 
                            ?>
                                <label class="option-item">
                                    <input type="checkbox" name="subject_ids[]" value="<?= $row['id'] ?>" <?= (isset($subject_ids) && is_array($subject_ids) && in_array($row['id'], $subject_ids)) ? 'checked' : '' ?>>
                                    <span class="option-text"><?= htmlspecialchars($row['name']) ?></span>
                                </label>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <div id="subject-chips" class="subject-chips"></div>
            </fieldset>

            <fieldset>
                <legend>Photo</legend>
                <label for="photo">Upload Photo</label>
                <input type="file" id="photo" name="photo" accept="image/*">
            </fieldset>

            <button type="submit" class="generatebutton">➕ Add Teacher</button>
        </form>
    </div>

    <script>
        // Global function to add education entry
        function addEducation() {
            var educationEntries = document.getElementById('education-entries');
            if (!educationEntries) return;
            
            var newEntry = document.createElement('div');
            newEntry.className = 'education-entry';
            
            // Build the HTML for the new entry
            var html = '';
            html += '<label>Education *</label>';
            html += '<select name="education[]" class="education-select">';
            html += '<option value="">Select Education</option>';
            html += '<option value="SSC">SSC</option>';
            html += '<option value="HSC">HSC</option>';
            html += '<option value="Diploma">Diploma</option>';
            html += '<option value="BSc">BSc</option>';
            html += '<option value="MSc">MSc</option>';
            html += '<option value="BA">BA</option>';
            html += '<option value="MA">MA</option>';
            html += '<option value="BSS">BSS</option>';
            html += '<option value="MSS">MSS</option>';
            html += '<option value="BA(Hons)">BA(Hons)</option>';
            html += '<option value="MA(Pro)">MA(Pro)</option>';
            html += '<option value="BBA">BBA</option>';
            html += '<option value="MBA">MBA</option>';
            html += '<option value="MBBS">MBBS</option>';
            html += '<option value="BSc Engineering">BSc Engineering</option>';
            html += '<option value="MSc Engineering">MSc Engineering</option>';
            html += '<option value="Bachelor">Bachelor</option>';
            html += '<option value="Master">Master</option>';
            html += '<option value="PhD">PhD</option>';
            html += '</select>';
            html += '<label>Education Institute</label>';
            html += '<input type="text" name="institute[]" placeholder="Enter institute name">';
            html += '<label>Result</label>';
            html += '<input type="text" name="result[]" placeholder="Enter result">';
            html += '<button type="button" class="remove-education-btn">&times;</button>';
            
            newEntry.innerHTML = html;
            educationEntries.appendChild(newEntry);
            
            // Add remove functionality
            var removeBtn = newEntry.querySelector('.remove-education-btn');
            removeBtn.addEventListener('click', function() {
                newEntry.remove();
                updateEducationRemoveButtons();
            });
            
            updateEducationRemoveButtons();
        }
        
        function updateEducationRemoveButtons() {
            var educationEntries = document.getElementById('education-entries');
            if (!educationEntries) return;
            
            var entries = educationEntries.querySelectorAll('.education-entry');
            entries.forEach(function(entry) {
                var btn = entry.querySelector('.remove-education-btn');
                if (entries.length > 1) {
                    btn.style.display = 'flex';
                } else {
                    btn.style.display = 'none';
                }
            });
        }
        
        document.addEventListener('DOMContentLoaded', function () {
            // Education Entry - Add More
            const addEducationBtn = document.getElementById('add-education-btn');
            const educationEntries = document.getElementById('education-entries');
            
            if (addEducationBtn && educationEntries) {
                addEducationBtn.addEventListener('click', function() {
                    var newEntry = document.createElement('div');
                    newEntry.className = 'education-entry';
                    
                    // Build the HTML for the new entry
                    var html = '';
                    html += '<label>Education *</label>';
                    html += '<select name="education[]" class="education-select">';
                    html += '<option value="">Select Education</option>';
                    html += '<option value="এসএসসি">এসএসসি</option>';
                    html += '<option value="এইচএসসি">এইচএসসি</option>';
                    html += '<option value="ডিপ্লোমা">ডিপ্লোমা</option>';
                    html += '<option value="বিএসসি">বিএসসি</option>';
                    html += '<option value="এমএসসি">এমএসসি</option>';
                    html += '<option value="বিএ">বিএ</option>';
                    html += '<option value="এমএ">এমএ</option>';
                    html += '<option value="বিএসএস">বিএসএস</option>';
                    html += '<option value="এমএসএস">এমএসএস</option>';
                    html += '<option value="বিএ(অনার্স)">বিএ(অনার্স)</option>';
                    html += '<option value="এমএ(অনার্স)">এমএ(অনার্স)</option>';
                    html += '<option value="বিবিএ">বিবিএ</option>';
                    html += '<option value="এমবিএ">এমবিএ</option>';
                    html += '<option value="এমবিবিএ">এমবিবিএ</option>';
                    html += '<option value="বি এস সি ইন্জিনিয়ারিং">বি এস সি ইন্জিনিয়ারিং</option>';
                    html += '<option value="এম এস সি ইন্জিনিয়ারিং">এম এস সি ইন্জিনিয়ারিং</option>';
                    html += '</select>';
                    html += '<label>Education Institute</label>';
                    html += '<input type="text" name="institute[]" placeholder="Enter institute name">';
                    html += '<label>Result</label>';
                    html += '<input type="text" name="result[]" placeholder="Enter result">';
                    html += '<button type="button" class="remove-education-btn">&times;</button>';
                    
                    newEntry.innerHTML = html;
                    educationEntries.appendChild(newEntry);
                    
                    // Add remove functionality
                    var removeBtn = newEntry.querySelector('.remove-education-btn');
                    removeBtn.addEventListener('click', function() {
                        newEntry.remove();
                        updateRemoveButtons();
                    });
                    
                    // Show remove button on all entries
                    updateRemoveButtons();
                });
                
                // Function to update remove buttons visibility
                function updateRemoveButtons() {
                    var entries = educationEntries.querySelectorAll('.education-entry');
                    entries.forEach(function(entry, index) {
                        var btn = entry.querySelector('.remove-education-btn');
                        if (entries.length > 1) {
                            btn.style.display = 'flex';
                        } else {
                            btn.style.display = 'none';
                        }
                    });
                }
                
                // Initialize remove buttons
                updateRemoveButtons();
            }

            // Subject Multiselect (existing)
            const multiselectSelected = document.getElementById('multiselect-selected');
            const multiselectDropdown = document.getElementById('multiselect-dropdown');
            const searchInput = document.getElementById('subject-search');
            const optionsContainer = document.getElementById('options-container');
            const chipsContainer = document.getElementById('subject-chips');
            const checkboxes = optionsContainer.querySelectorAll('input[type="checkbox"]');
            const pwdInput = document.getElementById('login_password');
            const toggleBtn = document.getElementById('togglePassword');

            // Toggle dropdown
            multiselectSelected.addEventListener('click', function (e) {
                e.stopPropagation();
                multiselectDropdown.classList.toggle('show');
                multiselectSelected.classList.toggle('active');
                if (multiselectDropdown.classList.contains('show')) {
                    searchInput.focus();
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function () {
                multiselectDropdown.classList.remove('show');
                multiselectSelected.classList.remove('active');
            });

            multiselectDropdown.addEventListener('click', function (e) {
                e.stopPropagation();
            });

            // Search functionality
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                checkboxes.forEach(cb => {
                    const label = cb.closest('.option-item');
                    const text = label.querySelector('.option-text').textContent.toLowerCase();
                    label.style.display = text.includes(searchTerm) ? 'flex' : 'none';
                });
            });

            // Handle checkbox change
            checkboxes.forEach(cb => {
                cb.addEventListener('change', renderChips);
            });

            // Render chips
            function renderChips() {
                chipsContainer.innerHTML = '';
                const selectedValues = [];
                
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        selectedValues.push({ value: cb.value, text: cb.nextElementSibling.textContent });
                    }
                });

                if (selectedValues.length === 0) {
                    multiselectSelected.innerHTML = '<span class="placeholder">Click to select subjects...</span><span class="dropdown-arrow">&#9662;</span>';
                } else if (selectedValues.length <= 2) {
                    multiselectSelected.innerHTML = selectedValues.map(s => `<span class="selected-label">${s.text}</span>`).join(', ') + '<span class="dropdown-arrow">&#9662;</span>';
                } else {
                    multiselectSelected.innerHTML = `<span class="selected-label">${selectedValues.length} subjects selected</span><span class="dropdown-arrow">&#9662;</span>`;
                }

                selectedValues.forEach(s => {
                    const chip = document.createElement('span');
                    chip.className = 'chip';
                    chip.dataset.value = s.value;
                    const label = document.createElement('span');
                    label.textContent = s.text;
                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'remove';
                    btn.innerHTML = '&times;';
                    btn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const cb = document.querySelector(`input[name="subject_ids[]"][value="${s.value}"]`);
                        if (cb) cb.checked = false;
                        renderChips();
                    });
                    chip.appendChild(label);
                    chip.appendChild(btn);
                    chipsContainer.appendChild(chip);
                });
            }

            // Initial render
            renderChips();

            // Password toggle (swap SVG icons)
            if (toggleBtn && pwdInput) {
                const iconShow = document.getElementById('teacherIconShow');
                const iconHide = document.getElementById('teacherIconHide');
                toggleBtn.addEventListener('click', function () {
                    if (pwdInput.type === 'password') {
                        pwdInput.type = 'text';
                        iconShow.style.display = 'none';
                        iconHide.style.display = 'inline';
                        toggleBtn.setAttribute('aria-label', 'Hide password');
                    } else {
                        pwdInput.type = 'password';
                        iconShow.style.display = 'inline';
                        iconHide.style.display = 'none';
                        toggleBtn.setAttribute('aria-label', 'Show password');
                    }
                    pwdInput.focus();
                });
                // allow toggle via keyboard Enter/Space
                toggleBtn.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleBtn.click(); }
                });
            }
        });
    </script>

</body>

</html>
