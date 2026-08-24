<?php
// Session check for user role access
session_start();
include 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Moto Shop Online Store</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .profile-container {
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: 2rem;
            max-width: 1000px;
            margin: 0 auto;
            align-items: start;
        }

        .profile-card,
        .details-card {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 2.5rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
        }

        .profile-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 120px;
            background: linear-gradient(135deg, rgba(0, 123, 255, 0.2), rgba(40, 167, 69, 0.2));
            z-index: 0;
        }

        .profile-card>* {
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 130px;
            height: 130px;
            background: linear-gradient(135deg, var(--accent-blue), #00d2ff);
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3.5rem;
            color: white;
            font-weight: 800;
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.4), inset 0 2px 5px rgba(255, 255, 255, 0.4);
            border: 4px solid rgba(30, 30, 40, 0.9);
            margin-top: -10px;
        }

        .profile-input {
            width: 100%;
            padding: 0.9rem 1.2rem;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            color: var(--text-color);
            margin-bottom: 1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 0.95rem;
        }

        .profile-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        }

        .profile-input:read-only {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.05);
            color: #ccc;
        }

        .profile-input:not(:read-only) {
            background: rgba(255, 255, 255, 0.08);
            border-color: var(--accent-blue);
            color: white;
        }

        .profile-label {
            display: block;
            color: #8892b0;
            font-size: 0.8rem;
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        .info-group {
            background: rgba(0, 0, 0, 0.2);
            padding: 1rem 1.2rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.03);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-group .profile-label {
            margin-bottom: 0;
            font-size: 0.75rem;
        }

        .info-group p {
            margin: 0;
            font-weight: 600;
            color: #e6f1ff;
        }

        .btn-edit,
        .btn-save {
            padding: 0.8rem 1.8rem;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .btn-edit {
            background: transparent;
            color: var(--accent-blue);
            border: 1px solid var(--accent-blue);
        }

        .btn-edit:hover {
            background: rgba(0, 123, 255, 0.1);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.2);
        }

        .btn-save {
            background: linear-gradient(135deg, var(--accent-green), #20c997);
            color: white;
            border: none;
            display: none;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.5);
        }

        .section-title {
            color: white;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title h3 {
            font-size: 1.4rem;
            position: relative;
        }

        .section-title h3::after {
            content: '';
            position: absolute;
            left: 0;
            bottom: -17px;
            width: 40px;
            height: 3px;
            background: var(--accent-blue);
            border-radius: 2px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.2rem;
        }

        @media (max-width: 850px) {
            .profile-container {
                grid-template-columns: 1fr;
                max-width: 500px;
            }
        }

        @media (max-width: 600px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navdiv">
            <div class="logo">
                <a href="home.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18.5" cy="17.5" r="3.5"/>
                        <circle cx="5.5" cy="17.5" r="3.5"/>
                        <circle cx="15" cy="5" r="1"/>
                        <path d="M12 17.5V14l-3-3 4-3 2 3h2"/>
                    </svg>
                    <span style="font-weight: 900; color: #ffffff; letter-spacing: 0.5px;">MOTO<span style="color: var(--accent-orange, #ff5500); font-weight: 400;">SHOP</span></span>
                </a>
            </div>
            <ul>
                <li><a href="home.php">Store</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="profile.php" style="color: var(--accent-blue);">Profile</a></li>
                <button onclick="window.location.href='logout.php'">Log out</button>
            </ul>
        </div>
    </nav>

    <div class="center-content">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">

            <div class="profile-container">
                <!-- Left Sidebar: Basic Info -->
                <div class="profile-card" style="text-align: center;">
                    <div class="profile-avatar" id="avatarInitial">?</div>
                    <h2 id="displayFullName" style="color: white; margin-bottom: 0.5rem;">Loading...</h2>
                    <p id="displayUsername" style="color: var(--accent-blue); margin-bottom: 2rem;">@username</p>

                    <div style="text-align: left; margin-bottom: 1rem; width: 100%;">
                        <div class="info-group">
                            <span class="profile-label">ID Number</span>
                            <p id="displayIdno">----</p>
                        </div>

                        <div class="info-group">
                            <span class="profile-label">Sex</span>
                            <p id="displaySex">----</p>
                        </div>

                        <div class="info-group">
                            <span class="profile-label">Birthdate</span>
                            <p id="displayBirthdate">----</p>
                        </div>
                    </div>
                </div>

                <!-- Right Main Content: Editable Info -->
                <div class="details-card">
                    <form id="profileForm">
                        <div class="section-title">
                            <h3>Personal Information</h3>
                            <div>
                                <button type="button" class="btn-edit" id="editBtn" onclick="toggleEdit()">Edit
                                    Profile</button>
                                <button type="submit" class="btn-save" id="saveBtn">Save Changes</button>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <span class="profile-label">First Name</span>
                                <input type="text" class="profile-input" id="firstname" readonly required>
                            </div>
                            <div>
                                <span class="profile-label">Last Name</span>
                                <input type="text" class="profile-input" id="lastname" readonly required>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <span class="profile-label">Email Address</span>
                                <input type="email" class="profile-input" id="email" readonly required>
                            </div>
                        </div>

                        <div class="section-title" style="margin-top: 2rem;">
                            <h3>Address Details</h3>
                        </div>

                        <div class="form-grid">
                            <div>
                                <span class="profile-label">Purok / Street</span>
                                <input type="text" class="profile-input" id="purokstreet" readonly>
                            </div>
                            <div>
                                <span class="profile-label">Barangay</span>
                                <input type="text" class="profile-input" id="brgy" readonly>
                            </div>
                            <div>
                                <span class="profile-label">Municipality</span>
                                <input type="text" class="profile-input" id="municipal" readonly>
                            </div>
                            <div>
                                <span class="profile-label">Province</span>
                                <input type="text" class="profile-input" id="province" readonly>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <span class="profile-label">Zip Code</span>
                                <input type="text" class="profile-input" id="zipcode" readonly>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast"
        style="display: none; position: fixed; bottom: 20px; right: 20px; background: var(--accent-green); color: white; padding: 1rem 2rem; border-radius: 8px; font-weight: bold; box-shadow: 0 10px 20px rgba(0,0,0,0.5); z-index: 2000;">
        Profile updated!
    </div>

    <footer>
        <p>&copy; 2024 Moto Shop. All rights reserved.</p>
    </footer>

    <script>
        let isEditing = false;

        document.addEventListener('DOMContentLoaded', loadProfile);

        function loadProfile() {
            fetch('api/profile_api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const user = data.user;

                        // Populate displays
                        document.getElementById('displayFullName').textContent = `${user.firstname} ${user.lastname}`;
                        document.getElementById('displayUsername').textContent = `@${user.username}`;
                        document.getElementById('displayIdno').textContent = user.idno;
                        document.getElementById('displaySex').textContent = user.sex;
                        document.getElementById('displayBirthdate').textContent = user.birthdate;

                        // Set avatar initial
                        if (user.firstname) {
                            document.getElementById('avatarInitial').textContent = user.firstname.charAt(0).toUpperCase();
                        }

                        // Populate form inputs
                        document.getElementById('firstname').value = user.firstname || '';
                        document.getElementById('lastname').value = user.lastname || '';
                        document.getElementById('email').value = user.email || '';
                        document.getElementById('purokstreet').value = user.purokstreet || '';
                        document.getElementById('brgy').value = user.brgy || '';
                        document.getElementById('municipal').value = user.municipal || '';
                        document.getElementById('province').value = user.province || '';
                        document.getElementById('zipcode').value = user.zipcode || '';
                    } else {
                        alert(data.error || 'Failed to load profile');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to connect to server');
                });
        }

        function toggleEdit() {
            isEditing = !isEditing;
            const inputs = document.querySelectorAll('.profile-input');
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');

            inputs.forEach(input => {
                if (isEditing) {
                    input.removeAttribute('readonly');
                } else {
                    input.setAttribute('readonly', true);
                }
            });

            if (isEditing) {
                editBtn.textContent = 'Cancel Edit';
                saveBtn.style.display = 'inline-block';
            } else {
                editBtn.textContent = 'Edit Profile';
                saveBtn.style.display = 'none';
                loadProfile(); // Reset to original values
            }
        }

        document.getElementById('profileForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const data = {
                firstname: document.getElementById('firstname').value,
                lastname: document.getElementById('lastname').value,
                email: document.getElementById('email').value,
                purokstreet: document.getElementById('purokstreet').value,
                brgy: document.getElementById('brgy').value,
                municipal: document.getElementById('municipal').value,
                province: document.getElementById('province').value,
                zipcode: document.getElementById('zipcode').value
            };

            fetch('api/profile_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(res => {
                    const toast = document.getElementById('toast');
                    if (res.success) {
                        toast.style.background = 'var(--accent-green)';
                        toast.textContent = "✅ Profile updated successfully!";

                        // Exit edit mode
                        isEditing = true; // Set to true so toggleEdit flips it to false
                        toggleEdit();
                    } else {
                        toast.style.background = 'var(--accent-red)';
                        toast.textContent = "❌ Failed: " + (res.error || "Could not update profile");
                    }

                    toast.style.display = 'block';
                    setTimeout(() => {
                        toast.style.display = 'none';
                    }, 3000);
                })
                .catch(err => {
                    alert("Error updating profile: " + err);
                });
        });
    </script>
</body>

</html>