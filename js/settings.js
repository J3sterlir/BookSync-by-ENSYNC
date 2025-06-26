document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('settingsForm');

    form.addEventListener('submit', (e) => {
        const currentPassword = form.current_password.value.trim();
        const newPassword = form.new_password.value.trim();
        const confirmPassword = form.confirm_password.value.trim();

        if (currentPassword || newPassword || confirmPassword) {
            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('To change your password, please fill in all password fields.');
                e.preventDefault();
                return;
            }

            if (newPassword.length < 6) {
                alert('New password must be at least 6 characters long.');
                e.preventDefault();
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('New password and confirmation do not match.');
                e.preventDefault();
                return;
            }
        }
    });

    const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');

            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Remove active class from all tab links and contents
                    tabLinks.forEach(l => {
                        l.classList.remove('active');
                        l.setAttribute('aria-selected', 'false');
                    });
                    tabContents.forEach(c => {
                        c.classList.remove('active');
                        c.hidden = true; // Hide all tab contents
                    });

                    // Add active class to the clicked tab link
                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');

                    // Show the corresponding tab content
                    const tabId = this.getAttribute('data-tab');
                    const activeTabContent = document.getElementById(tabId);
                    activeTabContent.classList.add('active');
                    activeTabContent.hidden = false; // Show the selected tab content
                });
            });


});

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('settingsForm');

    form.addEventListener('submit', (e) => {
        const currentPassword = form.current_password.value.trim();
        const newPassword = form.new_password.value.trim();
        const confirmPassword = form.confirm_password.value.trim();

        if (currentPassword || newPassword || confirmPassword) {
            if (!currentPassword || !newPassword || !confirmPassword) {
                alert('To change your password, please fill in all password fields.');
                e.preventDefault();
                return;
            }

            if (newPassword.length < 6) {
                alert('New password must be at least 6 characters long.');
                e.preventDefault();
                return;
            }

            if (newPassword !== confirmPassword) {
                alert('New password and confirmation do not match.');
                e.preventDefault();
                return;
            }
        }
    });

    const tabLinks = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');

            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    // Remove active class from all tab links and contents
                    tabLinks.forEach(l => {
                        l.classList.remove('active');
                        l.setAttribute('aria-selected', 'false');
                    });
                    tabContents.forEach(c => {
                        c.classList.remove('active');
                        c.hidden = true; // Hide all tab contents
                    });

                    // Add active class to the clicked tab link
                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');

                    // Show the corresponding tab content
                    const tabId = this.getAttribute('data-tab');
                    const activeTabContent = document.getElementById(tabId);
                    activeTabContent.classList.add('active');
                    activeTabContent.hidden = false; // Show the selected tab content
                });
            });


});

