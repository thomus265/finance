/**
 * Chart.js Loader
 * 
 * Handles loading Chart.js with multiple fallback options to ensure compatibility
 */

(function() {
    'use strict';
    
    // Chart.js loading configuration
    const CHART_CONFIGS = [
        {
            name: 'Chart.js v4 UMD',
            url: 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js',
            version: '4.4.0'
        },
        {
            name: 'Chart.js v4 UMD (cdnjs)',
            url: 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.js',
            version: '4.4.0'
        },
        {
            name: 'Chart.js v3 (legacy fallback)',
            url: 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
            version: '3.9.1'
        },
        {
            name: 'Chart.js v3 (cdnjs legacy)',
            url: 'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js',
            version: '3.9.1'
        }
    ];
    
    let currentConfigIndex = 0;
    let loadAttempts = 0;
    const maxAttempts = CHART_CONFIGS.length;
    
    // Check if Chart.js is already loaded
    if (typeof Chart !== 'undefined') {
        console.log('✅ Chart.js already loaded:', Chart.version);
        triggerChartReady();
        return;
    }
    
    // Start loading process
    loadChartJs();
    
    function loadChartJs() {
        if (loadAttempts >= maxAttempts) {
            console.error('❌ All Chart.js loading attempts failed');
            triggerChartError('Failed to load Chart.js from all sources');
            return;
        }
        
        const config = CHART_CONFIGS[currentConfigIndex];
        console.log(`🔄 Attempting to load ${config.name} (attempt ${loadAttempts + 1}/${maxAttempts})`);
        
        const script = document.createElement('script');
        script.src = config.url;
        script.async = true;
        
        script.onload = function() {
            // Verify Chart.js is actually available
            if (typeof Chart !== 'undefined') {
                console.log(`✅ ${config.name} loaded successfully`);
                console.log('📊 Chart.js version:', Chart.version || config.version);
                triggerChartReady();
            } else {
                console.warn(`⚠️ ${config.name} loaded but Chart object not available`);
                tryNextConfig();
            }
        };
        
        script.onerror = function() {
            console.warn(`❌ Failed to load ${config.name}`);
            tryNextConfig();
        };
        
        // Timeout fallback
        setTimeout(() => {
            if (typeof Chart === 'undefined') {
                console.warn(`⏰ ${config.name} loading timed out`);
                tryNextConfig();
            }
        }, 10000);
        
        document.head.appendChild(script);
        loadAttempts++;
    }
    
    function tryNextConfig() {
        currentConfigIndex++;
        if (currentConfigIndex < CHART_CONFIGS.length) {
            setTimeout(loadChartJs, 500); // Small delay before next attempt
        } else {
            console.error('❌ All Chart.js configurations failed');
            triggerChartError('Unable to load Chart.js from any source');
        }
    }
    
    function triggerChartReady() {
        // Dispatch custom event
        const event = new CustomEvent('chartjs:ready', {
            detail: {
                version: Chart.version || 'unknown',
                loadAttempts: loadAttempts,
                source: CHART_CONFIGS[currentConfigIndex]?.name || 'unknown'
            }
        });
        document.dispatchEvent(event);
        
        // Set global flag
        window.chartJsReady = true;
        
        // Execute any queued chart initializations
        if (window.chartInitQueue) {
            console.log(`🚀 Executing ${window.chartInitQueue.length} queued chart initializations`);
            window.chartInitQueue.forEach(fn => {
                try {
                    fn();
                } catch (error) {
                    console.error('Error executing queued chart initialization:', error);
                }
            });
            window.chartInitQueue = [];
        }
    }
    
    function triggerChartError(message) {
        const event = new CustomEvent('chartjs:error', {
            detail: { message: message, attempts: loadAttempts }
        });
        document.dispatchEvent(event);
        
        // Show fallback UI
        showChartFallback(message);
    }
    
    function showChartFallback(message) {
        const chartContainers = document.querySelectorAll('canvas[id*="Chart"], canvas[id*="chart"], div[id*="Chart"], div[id*="chart"]');
        
        chartContainers.forEach(container => {
            const fallbackHtml = `
                <div class="chart-fallback text-center p-4" style="border: 2px dashed #dee2e6; border-radius: 8px; background: #f8f9fa;">
                    <div class="mb-3">
                        <i class="bi bi-bar-chart" style="font-size: 2rem; color: #6c757d;"></i>
                    </div>
                    <h6 class="text-muted">Chart Unavailable</h6>
                    <p class="small text-muted mb-3">${message}</p>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-primary" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                        <button class="btn btn-outline-secondary" onclick="window.chartLoader.retry()">
                            <i class="bi bi-download"></i> Retry
                        </button>
                    </div>
                </div>
            `;
            
            if (container.tagName.toLowerCase() === 'canvas') {
                // Replace canvas with div
                const wrapper = document.createElement('div');
                wrapper.id = container.id;
                wrapper.className = container.className;
                wrapper.innerHTML = fallbackHtml;
                container.parentNode.replaceChild(wrapper, container);
            } else {
                container.innerHTML = fallbackHtml;
            }
        });
    }
    
    // Global chart loader object
    window.chartLoader = {
        retry: function() {
            currentConfigIndex = 0;
            loadAttempts = 0;
            loadChartJs();
        },
        
        isReady: function() {
            return typeof Chart !== 'undefined';
        },
        
        whenReady: function(callback) {
            if (this.isReady()) {
                callback();
            } else {
                if (!window.chartInitQueue) {
                    window.chartInitQueue = [];
                }
                window.chartInitQueue.push(callback);
            }
        }
    };
    
    // Enhanced chart initialization helper
    window.initChartWhenReady = function(chartId, chartConfig, callback) {
        window.chartLoader.whenReady(() => {
            try {
                const canvas = document.getElementById(chartId);
                if (!canvas) {
                    console.error(`Chart canvas '${chartId}' not found`);
                    return;
                }
                
                const chart = new Chart(canvas, chartConfig);
                console.log(`✅ Chart '${chartId}' initialized successfully`);
                
                if (callback) callback(chart);
                return chart;
            } catch (error) {
                console.error(`❌ Failed to initialize chart '${chartId}':`, error);
                if (callback) callback(null, error);
            }
        });
    };
    
    console.log('📊 Chart.js loader initialized');
    
})();
