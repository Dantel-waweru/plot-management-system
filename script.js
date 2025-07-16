document.getElementById('menu-toggle').addEventListener('click', function() {
    let sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('collapsed');
});


    <!-- JavaScript for sidebar toggle -->
    <script>
       function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
}
document.addEventListener('DOMContentLoaded', function () {
    const recentActivitiesList = document.getElementById('recentActivitiesList');

    // Fetch all notifications (or just recent ones depending on your backend)
    fetch('fetch_notifications.php')
        .then(res => res.json())
        .then(data => {
            recentActivitiesList.innerHTML = ''; // Clear old list

            if (data.length === 0) {
                recentActivitiesList.innerHTML = '<li>No recent activities</li>';
            } else {
                data.forEach(notif => {
                    const li = document.createElement('li');
                    // Customize message format here, e.g.:
                    li.textContent = `${notif.name}: ${notif.message}`;
                    recentActivitiesList.appendChild(li);
                });
            }
        })
        .catch(err => {
            console.error('Error loading recent activities:', err);
            recentActivitiesList.innerHTML = '<li>Error loading activities</li>';
        });
});

    
document.querySelectorAll('.stat-card .count').forEach(counter => {
  const target = parseFloat(counter.getAttribute('data-target'));
  const isMoney = counter.innerText.includes("KES");
  let count = 0;
  const duration = 700;
  const steps = duration / 50;
  const increment = target / steps;

  function updateCount() {
    counter.classList.remove('visible');
    setTimeout(() => {
      count += increment;
      if (count < target) {
        counter.innerText = isMoney ? 'KES ' + count.toFixed(2) : Math.floor(count);
        counter.classList.add('visible');
        setTimeout(updateCount, 50);
      } else {
        counter.innerText = isMoney ? 'KES ' + target.toFixed(2) : Math.floor(target);
        counter.classList.add('visible');

        // Start fade in/out and color transition after count finishes
        setTimeout(() => {
          counter.classList.add('animate-loop');
        }, 500);
      }
    }, 200);
  }

  counter.classList.add('visible');
  updateCount();
});



document.addEventListener('DOMContentLoaded', function () {
    const notifButton = document.querySelector('.taskbar-btn'); // 🔔 button
    const modal = document.getElementById('notifModal');
    const notifList = document.getElementById('notifList');

    // Show all notifications on button click
    notifButton.addEventListener('click', function () {
        fetch('fetch_notifications.php')
            .then(res => res.json())
            .then(data => {
                notifList.innerHTML = '';
                if (data.length === 0) {
                    notifList.innerHTML = '<p>No notifications</p>';
                } else {
                    data.forEach(notif => {
                        notifList.innerHTML += `
                            <div style="border-bottom:1px solid #ccc; margin-bottom:10px; padding-bottom:10px;">
                                <strong>${notif.name}</strong><br>
                                📞 ${notif.phone}<br>
                                📧 ${notif.email}<br>
                                💬 ${notif.message}<br>
                                <small style="color:${notif.status === 'unread' ? 'red' : 'gray'}">
                                    ${notif.status === 'unread' ? 'Unread' : 'Read'}
                                </small>
                            </div>
                        `;
                    });
                }
                modal.style.display = 'flex'; // Open modal
            });
    });

    // On page load: check for unread and auto-popup
    fetch('fetch_unread_notifications.php')
        .then(res => res.json())
        .then(data => {
            if (data.length > 0) {
                notifList.innerHTML = `<p><strong>You have ${data.length} new notification(s):</strong></p>`;
                data.forEach(notif => {
                    notifList.innerHTML += `
                        <div style="border-bottom:1px solid #ccc; margin-bottom:10px; padding-bottom:10px;">
                            <strong>${notif.name}</strong><br>
                            📞 ${notif.phone}<br>
                            📧 ${notif.email}<br>
                            💬 ${notif.message}
                        </div>
                    `;

                });
                modal.style.display = 'flex';

                // Mark as read
                fetch('mark_notifications_read.php', { method: 'POST' });
            }
        });

    // Click outside to close
    window.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
});
