document.addEventListener('DOMContentLoaded', () => {
    const endpoint = document.body.dataset.maintenanceEndpoint;
    const lists = Array.from(document.querySelectorAll('[data-maintenance-list]'));
    if (!endpoint || lists.length === 0) return;

    const node = (tag, text, className) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined && text !== null) element.textContent = text;
        return element;
    };

    const renderHistory = (events) => {
        const details = node('details');
        details.append(node('summary', 'Progress history'));
        const list = node('ul');
        if (!events.length) {
            list.append(node('li', 'No progress history yet.'));
        } else {
            events.forEach((event) => {
                const item = node('li');
                const actor = event.actor_role ? `${event.actor_name} (${event.actor_role})` : event.actor_name;
                item.append(node('strong', `${event.action.replaceAll('_', ' ')} · ${actor}`));
                const transition = event.previous_status || event.new_status
                    ? ` · ${event.previous_status || '—'} → ${event.new_status || '—'}`
                    : '';
                item.append(node('span', `${event.created_at}${transition}`));
                if (event.response) item.append(node('p', event.response));
                list.append(item);
            });
        }
        details.append(list);
        return details;
    };

    const renderRequest = (request) => {
        const article = node('article', null, 'activity-item');
        const content = node('div');
        content.append(node('strong', `${request.reference_no} · ${request.category}`));
        content.append(node('span', `${request.title} · ${request.priority} priority · ${request.created_at}`));
        const location = [request.hostel_name_snapshot || 'Hostel not assigned',
            request.room_number_snapshot ? `Room ${request.room_number_snapshot}` : null].filter(Boolean).join(' · ');
        content.append(node('span', location));
        content.append(node('p', request.description));
        if (request.latest_response) content.append(node('p', `Latest response: ${request.latest_response}`));
        if (request.resolution_notes) content.append(node('p', `Resolution notes: ${request.resolution_notes}`));
        content.append(renderHistory(request.history || []));
        article.append(content);
        const status = node('span', request.status.replaceAll('_', ' '), `status-pill status-${request.status}`);
        article.append(status);
        return article;
    };

    const refresh = async () => {
        try {
            const response = await fetch(endpoint, { credentials: 'same-origin', cache: 'no-store' });
            if (!response.ok) throw new Error(`Refresh failed with HTTP ${response.status}`);
            const data = await response.json();
            lists.forEach((list) => {
                list.replaceChildren();
                const limit = Number(list.dataset.maintenanceLimit || data.requests.length);
                const requests = data.requests.slice(0, limit);
                if (!requests.length) {
                    list.append(node('p', 'You have not reported any issues.', 'dash-card-desc'));
                } else {
                    requests.forEach((request) => list.append(renderRequest(request)));
                }
            });
            Object.entries(data.summary).forEach(([name, count]) => {
                document.querySelectorAll(`[data-maintenance-count="${name}"]`).forEach((element) => {
                    element.textContent = String(count);
                });
            });
            document.querySelectorAll('[data-maintenance-refresh-status]').forEach((element) => {
                element.textContent = 'Updates automatically every 20 seconds.';
            });
        } catch (error) {
            document.querySelectorAll('[data-maintenance-refresh-status]').forEach((element) => {
                element.textContent = 'Could not refresh maintenance updates. Your existing information is still shown.';
            });
            console.error('CourtIQ maintenance refresh failed:', error);
        }
    };

    refresh();
    window.setInterval(refresh, 20000);
});
