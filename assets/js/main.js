document.addEventListener('DOMContentLoaded', () => {
    // Notification Bell Logic
    const notificationBell = document.getElementById('notification-bell');
    const notificationPanel = document.getElementById('notification-panel');
    const notificationCountEl = document.getElementById('notification-count');
    const notificationList = document.getElementById('notification-list');

    if (notificationBell) {
        notificationBell.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationPanel.classList.toggle('active');
            if (notificationPanel.classList.contains('active')) {
                fetchNotifications();
            }
        });

        document.addEventListener('click', () => {
            if (notificationPanel.classList.contains('active')) {
                notificationPanel.classList.remove('active');
            }
        });
        
        notificationPanel.addEventListener('click', e => e.stopPropagation());

        const fetchNotifications = async () => {
            try {
                const response = await fetch('../api/notifications.php');
                const data = await response.json();

                if(data.success) {
                    notificationCountEl.textContent = data.unread_count;
                    notificationCountEl.style.display = data.unread_count > 0 ? 'flex' : 'none';
                    renderNotifications(data.notifications);
                }
            } catch (error) {
                console.error('Error fetching notifications:', error);
            }
        };
        
        const renderNotifications = (notifications) => {
            if (notifications.length === 0) {
                 notificationList.innerHTML = '<p style="text-align:center; padding: 20px;">لا توجد إشعارات جديدة.</p>';
                 return;
            }
            notificationList.innerHTML = notifications.map(notif => `
                <div class="notification-item ${notif.is_read == '0' ? 'unread' : ''}" data-id="${notif.announcement_id}">
                    <div>
                        <h5>${notif.title}</h5>
                        <p>${notif.content.replace(/(<([^>]+)>)/ig, '').substring(0, 100)}...</p>
                        <small>${notif.created_at}</small>
                        <div class="notification-actions">
                             ${notif.is_read == '0' ? `<a href="#" class="mark-read">تعليم كمقروء</a>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        };

        notificationList.addEventListener('click', async (e) => {
            if (e.target.classList.contains('mark-read')) {
                e.preventDefault();
                const item = e.target.closest('.notification-item');
                const id = item.dataset.id;
                try {
                    await fetch('../api/notifications.php?action=mark_as_read&id=' + id);
                    fetchNotifications();
                } catch(err){ console.error(err) }
            }
        });

        fetchNotifications();
    }

});