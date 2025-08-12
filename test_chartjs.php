<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart.js Loading Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .status-success { background-color: #28a745; }
        .status-error { background-color: #dc3545; }
        .status-warning { background-color: #ffc107; }
        .status-loading { background-color: #6c757d; animation: pulse 1.5s infinite; }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .log-entry {
            font-family: monospace;
            font-size: 0.9em;
            padding: 4px 8px;
            margin: 2px 0;
            border-radius: 3px;
            background: #f8f9fa;
        }
        
        .log-success { border-left: 4px solid #28a745; }
        .log-error { border-left: 4px solid #dc3545; }
        .log-warning { border-left: 4px solid #ffc107; }
        .log-info { border-left: 4px solid #17a2b8; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="bi bi-bar-chart"></i> Chart.js Loading Test</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Loading Status</h5>
                                <div id="loading-status">
                                    <div class="mb-2">
                                        <span class="status-indicator status-loading" id="chartjs-indicator"></span>
                                        <span id="chartjs-status">Loading Chart.js...</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="status-indicator status-loading" id="loader-indicator"></span>
                                        <span id="loader-status">Initializing chart loader...</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="status-indicator status-loading" id="test-indicator"></span>
                                        <span id="test-status">Waiting for Chart.js...</span>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <h6>Chart.js Information</h6>
                                    <div id="chartjs-info">
                                        <p class="text-muted">Waiting for Chart.js to load...</p>
                                    </div>
                                </div>
                                
                                <div class="mt-4">
                                    <button class="btn btn-primary" onclick="testChart()">Test Chart Creation</button>
                                    <button class="btn btn-secondary" onclick="retryLoading()">Retry Loading</button>
                                    <button class="btn btn-info" onclick="clearLog()">Clear Log</button>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Test Chart</h5>
                                <div style="height: 300px; border: 1px solid #dee2e6; border-radius: 4px; position: relative;">
                                    <canvas id="testChart" width="400" height="300"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>Loading Log</h5>
                                <div id="loading-log" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; padding: 10px; border-radius: 4px; background: #f8f9fa;">
                                    <!-- Log entries will appear here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Load our chart loader -->
    <script src="/public/js/chart-loader.js"></script>
    
    <script>
        let testChart = null;
        
        // Override console.log to capture loading messages
        const originalLog = console.log;
        const originalError = console.error;
        const originalWarn = console.warn;
        
        function addLogEntry(message, type = 'info') {
            const logContainer = document.getElementById('loading-log');
            const entry = document.createElement('div');
            entry.className = `log-entry log-${type}`;
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logContainer.appendChild(entry);
            logContainer.scrollTop = logContainer.scrollHeight;
        }
        
        console.log = function(...args) {
            originalLog.apply(console, args);
            addLogEntry(args.join(' '), 'info');
        };
        
        console.error = function(...args) {
            originalError.apply(console, args);
            addLogEntry(args.join(' '), 'error');
        };
        
        console.warn = function(...args) {
            originalWarn.apply(console, args);
            addLogEntry(args.join(' '), 'warning');
        };
        
        // Update status indicators
        function updateStatus(elementId, status, message) {
            const indicator = document.getElementById(elementId + '-indicator');
            const statusText = document.getElementById(elementId + '-status');
            
            indicator.className = `status-indicator status-${status}`;
            statusText.textContent = message;
        }
        
        // Check initial state
        document.addEventListener('DOMContentLoaded', function() {
            addLogEntry('Page loaded, checking Chart.js availability...', 'info');
            
            if (typeof window.chartLoader !== 'undefined') {
                updateStatus('loader', 'success', 'Chart loader initialized');
                addLogEntry('Chart loader is available', 'success');
            } else {
                updateStatus('loader', 'error', 'Chart loader not found');
                addLogEntry('Chart loader not found!', 'error');
            }
            
            checkChartJs();
        });
        
        function checkChartJs() {
            if (typeof Chart !== 'undefined') {
                updateStatus('chartjs', 'success', `Chart.js v${Chart.version || 'unknown'} loaded`);
                updateStatus('test', 'success', 'Ready for testing');
                
                document.getElementById('chartjs-info').innerHTML = `
                    <p><strong>Version:</strong> ${Chart.version || 'unknown'}</p>
                    <p><strong>Available Controllers:</strong> ${Object.keys(Chart.registry.controllers.items).join(', ')}</p>
                    <p><strong>Available Elements:</strong> ${Object.keys(Chart.registry.elements.items).join(', ')}</p>
                `;
                
                addLogEntry(`Chart.js v${Chart.version || 'unknown'} is ready`, 'success');
            } else {
                updateStatus('chartjs', 'loading', 'Chart.js not yet loaded');
                setTimeout(checkChartJs, 500);
            }
        }
        
        // Listen for chart loader events
        document.addEventListener('chartjs:ready', function(event) {
            addLogEntry(`Chart.js ready: ${event.detail.source}`, 'success');
            updateStatus('chartjs', 'success', `Chart.js loaded from ${event.detail.source}`);
            updateStatus('test', 'success', 'Ready for testing');
            checkChartJs();
        });
        
        document.addEventListener('chartjs:error', function(event) {
            addLogEntry(`Chart.js loading failed: ${event.detail.message}`, 'error');
            updateStatus('chartjs', 'error', 'Chart.js loading failed');
            updateStatus('test', 'error', 'Cannot test - Chart.js unavailable');
        });
        
        function testChart() {
            addLogEntry('Testing chart creation...', 'info');
            
            if (typeof Chart === 'undefined') {
                addLogEntry('Cannot test - Chart.js not loaded', 'error');
                return;
            }
            
            const canvas = document.getElementById('testChart');
            if (!canvas) {
                addLogEntry('Test canvas not found', 'error');
                return;
            }
            
            // Destroy existing chart
            if (testChart) {
                testChart.destroy();
                addLogEntry('Destroyed existing test chart', 'info');
            }
            
            try {
                const config = {
                    type: 'line',
                    data: {
                        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                        datasets: [{
                            label: 'Test Data',
                            data: [12, 19, 3, 5, 2, 3],
                            borderColor: 'rgb(75, 192, 192)',
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Chart.js Test Chart'
                            }
                        }
                    }
                };
                
                testChart = new Chart(canvas, config);
                addLogEntry('Test chart created successfully!', 'success');
                
            } catch (error) {
                addLogEntry(`Chart creation failed: ${error.message}`, 'error');
            }
        }
        
        function retryLoading() {
            addLogEntry('Retrying Chart.js loading...', 'info');
            updateStatus('chartjs', 'loading', 'Retrying...');
            updateStatus('test', 'loading', 'Waiting...');
            
            if (window.chartLoader && window.chartLoader.retry) {
                window.chartLoader.retry();
            } else {
                addLogEntry('Chart loader retry function not available', 'error');
            }
        }
        
        function clearLog() {
            document.getElementById('loading-log').innerHTML = '';
            addLogEntry('Log cleared', 'info');
        }
    </script>
</body>
</html>
