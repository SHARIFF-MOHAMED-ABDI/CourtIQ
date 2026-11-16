document.addEventListener('DOMContentLoaded', () => {
    const tabs = Array.from(document.querySelectorAll('.dashboard-section-tabs [role="tab"]'));
    const panels = tabs
        .map((tab) => document.getElementById(tab.getAttribute('aria-controls')))
        .filter((panel) => panel);

    const activateTab = (selectedTab, moveFocus) => {
        tabs.forEach((tab) => {
            const isSelected = tab === selectedTab;
            tab.setAttribute('aria-selected', String(isSelected));
            tab.tabIndex = isSelected ? 0 : -1;

            const panel = document.getElementById(tab.getAttribute('aria-controls'));
            if (panel) panel.hidden = !isSelected;
        });

        if (moveFocus) selectedTab.focus();
    };

    if (tabs.length > 0 && panels.length === tabs.length) {
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => activateTab(tab, false));
            tab.addEventListener('keydown', (event) => {
                let nextIndex = index;
                if (event.key === 'ArrowRight') nextIndex = (index + 1) % tabs.length;
                else if (event.key === 'ArrowLeft') nextIndex = (index - 1 + tabs.length) % tabs.length;
                else if (event.key === 'Home') nextIndex = 0;
                else if (event.key === 'End') nextIndex = tabs.length - 1;
                else return;

                event.preventDefault();
                activateTab(tabs[nextIndex], true);
            });
        });
    }

    const endpoint = document.body.dataset.dashboardChartEndpoint;
    if (!endpoint) return;

    const svgNamespace = 'http://www.w3.org/2000/svg';
    const colors = ['#2563eb', '#0d9488', '#d97706', '#7c3aed', '#db2777', '#0891b2'];
    const refreshStatus = document.querySelector('[data-dashboard-refresh-status]');
    let refreshInProgress = false;

    const makeSvgElement = (name, attributes, text) => {
        const element = document.createElementNS(svgNamespace, name);
        Object.entries(attributes).forEach(([key, value]) => element.setAttribute(key, String(value)));
        if (text !== undefined) element.textContent = text;
        return element;
    };

    const renderChart = (container, chart) => {
        const width = 720;
        const height = 250;
        const left = 42;
        const right = 14;
        const top = 16;
        const bottom = 42;
        const plotWidth = width - left - right;
        const plotHeight = height - top - bottom;
        const entries = Object.entries(chart.series);
        const points = entries.flatMap(([, values]) => values.map((value) => Number(value) || 0));
        const maxValue = Math.max(1, Math.ceil(Math.max(0, ...points) * 1.15));
        const svg = makeSvgElement('svg', {
            class: 'line-chart-svg',
            viewBox: `0 0 ${width} ${height}`,
            role: 'img',
            'aria-label': chart.title,
        });

        for (let tick = 0; tick <= 3; tick += 1) {
            const value = Math.round(maxValue * tick / 3);
            const y = top + plotHeight - (plotHeight * tick / 3);
            svg.append(
                makeSvgElement('line', {
                    x1: left, y1: y.toFixed(1), x2: width - right, y2: y.toFixed(1), class: 'line-chart-grid',
                }),
                makeSvgElement('text', {
                    x: left - 10, y: (y + 4).toFixed(1), 'text-anchor': 'end', class: 'line-chart-axis',
                }, String(value)),
            );
        }

        chart.labels.forEach((label, index) => {
            const x = chart.labels.length > 1
                ? left + (plotWidth * index / (chart.labels.length - 1))
                : left + plotWidth / 2;
            svg.append(makeSvgElement('text', {
                x: x.toFixed(1),
                y: height - 12,
                'text-anchor': 'middle',
                class: 'line-chart-axis',
            }, String(label)));
        });

        entries.forEach(([name, values], seriesIndex) => {
            const color = colors[seriesIndex % colors.length];
            const coordinates = values.map((value, index) => {
                const x = values.length > 1
                    ? left + (plotWidth * index / (values.length - 1))
                    : left + plotWidth / 2;
                const y = top + plotHeight - (plotHeight * Math.max(0, Number(value) || 0) / maxValue);
                return { x: x.toFixed(1), y: y.toFixed(1) };
            });

            if (coordinates.length > 0) {
                svg.append(makeSvgElement('polyline', {
                    points: coordinates.map(({ x, y }) => `${x},${y}`).join(' '),
                    fill: 'none',
                    stroke: color,
                    'stroke-width': 3,
                    'stroke-linecap': 'round',
                    'stroke-linejoin': 'round',
                }));
                coordinates.forEach(({ x, y }) => {
                    svg.append(makeSvgElement('circle', {
                        cx: x, cy: y, r: 4, fill: color, stroke: '#fff', 'stroke-width': 2,
                    }));
                });
            }
        });

        const legend = document.createElement('div');
        legend.className = 'line-chart-legend';
        entries.forEach(([name], index) => {
            const item = document.createElement('span');
            const marker = document.createElement('i');
            marker.style.setProperty('--chart-series-color', colors[index % colors.length]);
            item.append(marker, document.createTextNode(name));
            legend.append(item);
        });
        container.replaceChildren(svg, legend);
    };

    const refreshCharts = async () => {
        if (refreshInProgress || document.hidden) return;
        refreshInProgress = true;
        try {
            const response = await fetch(endpoint, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });
            if (!response.ok || response.redirected) {
                throw new Error(`Chart refresh failed with HTTP ${response.status}.`);
            }
            const payload = await response.json();
            const charts = payload.charts;
            if (!charts || typeof charts !== 'object') {
                throw new Error('Chart data was not returned by the server.');
            }
            Object.entries(charts).forEach(([key, chart]) => {
                const container = document.querySelector(`[data-dashboard-chart="${key}"]`);
                if (container && chart && Array.isArray(chart.labels) && chart.series) {
                    renderChart(container, chart);
                }
            });
            if (refreshStatus) {
                refreshStatus.textContent = `Live charts updated at ${new Date().toLocaleTimeString()}.`;
            }
        } catch (error) {
            console.error('Unable to refresh administrator dashboard charts.', error);
            if (refreshStatus) refreshStatus.textContent = 'Live chart refresh unavailable; retrying.';
        } finally {
            refreshInProgress = false;
        }
    };

    refreshCharts();
    window.setInterval(refreshCharts, 15000);
});
