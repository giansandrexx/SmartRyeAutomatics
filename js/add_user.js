        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('formAction').value = 'add';
            document.getElementById('userForm').reset();
            document.getElementById('password').required = true;
            document.getElementById('passwordGroup').querySelector('small').style.display = 'none';
            document.getElementById('userModal').style.display = 'block';
        }

        function openEditModal(id, username, fullName, email, role) {
            document.getElementById('modalTitle').textContent = 'Edit User Account';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('userId').value = id;
            document.getElementById('username').value = username;
            document.getElementById('password').required = false;
            document.getElementById('password').value = '';
            document.getElementById('fullName').value = fullName;
            document.getElementById('email').value = email;
            document.getElementById('role').value = role;
            document.getElementById('passwordGroup').style.display = 'none';
            document.getElementById('userModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        function openPasswordModal(id, username) {
            document.getElementById('passwordUserId').value = id;
            document.getElementById('passwordUsername').textContent = 'Change password for: ' + username;
            document.getElementById('passwordForm').reset();
            document.getElementById('passwordModal').style.display = 'block';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
        }

        document.getElementById('confirmPassword').addEventListener('input', function() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = this.value;
            const matchText = document.getElementById('passwordMatch');
            const submitBtn = document.getElementById('passwordSubmit');
            
            if (confirmPassword === '') {
                matchText.textContent = '';
                matchText.style.color = '#666';
                submitBtn.disabled = false;
            } else if (newPassword === confirmPassword) {
                matchText.textContent = '✓ Passwords match';
                matchText.style.color = '#4CAF50';
                submitBtn.disabled = false;
            } else {
                matchText.textContent = '✗ Passwords do not match';
                matchText.style.color = '#C62828';
                submitBtn.disabled = true;
            }
        });

        document.getElementById('newPassword').addEventListener('input', function() {
            document.getElementById('confirmPassword').dispatchEvent(new Event('input'));
        });

        function deleteUser(id, username) {
            if (confirm('Are you sure you want to delete user "' + username + '"?\n\nThis action cannot be undone!')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        window.onclick = function(event) {
            const userModal = document.getElementById('userModal');
            const passwordModal = document.getElementById('passwordModal');
            if (event.target == userModal) {
                closeModal();
            }
            if (event.target == passwordModal) {
                closePasswordModal();
            }
        }