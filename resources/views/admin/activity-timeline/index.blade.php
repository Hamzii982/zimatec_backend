@extends('admin.layouts.index')

@section('title', 'Activity Timeline')

@php
    $currentDate = \Carbon\Carbon::now()->locale('de')->translatedFormat('l, d M Y');
@endphp

@section('content')
    <div class="container-fluid py-4">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-semi-bold">Activity Timeline</h4>
            <span class="text-muted">{{ $currentDate }}</span>
        </div>

        {{-- Filter Section --}}
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Filter Activity Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="startDate" class="form-label">Start Date</label>
                                <input type="date" id="startDate" class="form-control" 
                                    value="{{ $startDate->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4">
                                <label for="endDate" class="form-label">End Date</label>
                                <input type="date" id="endDate" class="form-control" 
                                    value="{{ $endDate->format('Y-m-d') }}">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button class="btn btn-primary w-100" id="filterBtn" onclick="updateActivityChart()">
                                    <i class="bi bi-funnel"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        <div class="row g-4 mb-4">
            {{-- Machine Activity Chart --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Machine Activity (Last {{ $endDate->diffInDays($startDate) + 1 }} Days)</h5>
                    </div>
                    <div class="card-body" style="position: relative; height: 300px;">
                        <canvas id="machineActivityChart"></canvas>
                    </div>
                    <div class="card-footer bg-light text-muted small">
                        <i class="bi bi-info-circle"></i> Click on a bar to view related time records
                    </div>
                </div>
            </div>

            {{-- User Activity Chart --}}
            <div class="col-lg-6">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">User Activity (Last {{ $endDate->diffInDays($startDate) + 1 }} Days)</h5>
                    </div>
                    <div class="card-body" style="position: relative; height: 300px;">
                        <canvas id="userActivityChart"></canvas>
                    </div>
                    <div class="card-footer bg-light text-muted small">
                        <i class="bi bi-info-circle"></i> Click on a bar to view related time records
                    </div>
                </div>
            </div>
        </div>

        {{-- Detailed Activity Table --}}
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Detailed Activity List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive-wrapper">
                            <table class="table table-hover align-middle mb-0" id="activityTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>User</th>
                                        <th>Machine</th>
                                        <th>Auftrag</th>
                                        <th>Position</th>
                                        <th>Duration (Hours)</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="activityTableBody">
                                    {{-- Populated by JavaScript --}}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Auftrag/Position tooltip --}}
    <div id="activityTooltip" class="position-absolute bg-white border border-secondary rounded p-2" 
        style="display: none; z-index: 1000; max-width: 300px;">
        <small>
            <strong>Auftrag:</strong> <span id="tooltipProject"></span><br>
            <strong>Position:</strong> <span id="tooltipPosition"></span>
        </small>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        let machineChart = null;
        let userChart = null;
        let activityData = null;

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadActivityData('{{ $startDate->format('Y-m-d') }}', '{{ $endDate->format('Y-m-d') }}');
        });

        /**
         * Load activity data from server via AJAX
         */
        function loadActivityData(startDate, endDate) {
            const url = `{{ route('admin.activity-timeline.data') }}?start_date=${startDate}&end_date=${endDate}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    activityData = data;
                    renderCharts(data);
                    renderActivityTable(data);
                })
                .catch(error => console.error('Error loading activity data:', error));
        }

        /**
         * Update charts when filter is applied
         */
        function updateActivityChart() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            
            if (!startDate || !endDate) {
                alert('Please select both start and end dates');
                return;
            }

            if (new Date(startDate) > new Date(endDate)) {
                alert('Start date must be before end date');
                return;
            }

            loadActivityData(startDate, endDate);
        }

        /**
         * Render Chart.js charts
         */
        function renderCharts(data) {
            // Prepare machine activity data
            const machineChartData = prepareMachineChartData(data.machineActivity.chartData);
            renderMachineChart(machineChartData);

            // Prepare user activity data
            const userChartData = prepareUserChartData(data.userActivity.chartData);
            renderUserChart(userChartData);
        }

        /**
         * Prepare machine activity data for Chart.js
         */
        function prepareMachineChartData(chartData) {
            // Group by machine
            const machineGroups = {};
            chartData.forEach(item => {
                if (!machineGroups[item.machine]) {
                    machineGroups[item.machine] = [];
                }
                machineGroups[item.machine].push(item);
            });

            // Get all unique dates
            const allDates = [...new Set(chartData.map(item => item.date))].sort();

            // Create datasets for each machine
            const datasets = Object.keys(machineGroups).map((machineName, index) => {
                const colors = [
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                    'rgba(255, 159, 64, 0.7)',
                    'rgba(201, 203, 207, 0.7)',
                ];

                const machineData = allDates.map(date => {
                    const record = machineGroups[machineName].find(item => item.date === date);
                    return record ? record.hours : 0;
                });

                return {
                    label: machineName,
                    data: machineData,
                    backgroundColor: colors[index % colors.length],
                    borderColor: colors[index % colors.length].replace('0.7', '1'),
                    borderWidth: 1,
                    machineData: machineGroups[machineName],
                };
            });

            return {
                labels: allDates.map(date => new Date(date).toLocaleDateString('de-DE', { month: 'short', day: 'numeric' })),
                datasets: datasets,
                dates: allDates,
            };
        }

        /**
         * Prepare user activity data for Chart.js
         */
        function prepareUserChartData(chartData) {
            // Group by user
            const userGroups = {};
            chartData.forEach(item => {
                if (!userGroups[item.user]) {
                    userGroups[item.user] = [];
                }
                userGroups[item.user].push(item);
            });

            // Get all unique dates
            const allDates = [...new Set(chartData.map(item => item.date))].sort();

            // Create datasets for each user
            const datasets = Object.keys(userGroups).map((userName, index) => {
                const colors = [
                    'rgba(255, 99, 132, 0.7)',
                    'rgba(54, 162, 235, 0.7)',
                    'rgba(75, 192, 192, 0.7)',
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(153, 102, 255, 0.7)',
                ];

                const userData = allDates.map(date => {
                    const record = userGroups[userName].find(item => item.date === date);
                    return record ? record.hours : 0;
                });

                return {
                    label: userName,
                    data: userData,
                    backgroundColor: colors[index % colors.length],
                    borderColor: colors[index % colors.length].replace('0.7', '1'),
                    borderWidth: 1,
                    userData: userGroups[userName],
                };
            });

            return {
                labels: allDates.map(date => new Date(date).toLocaleDateString('de-DE', { month: 'short', day: 'numeric' })),
                datasets: datasets,
                dates: allDates,
            };
        }

        /**
         * Render machine activity chart
         */
        function renderMachineChart(chartData) {
            const ctx = document.getElementById('machineActivityChart').getContext('2d');

            if (machineChart) {
                machineChart.destroy();
            }

            machineChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Hours',
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.x.toFixed(2) + ' hours';
                                },
                            },
                        },
                    },
                    onClick: function(event, activeElements) {
                        if (activeElements.length > 0) {
                            const element = activeElements[0];
                            const datasetIndex = element.datasetIndex;
                            const dataIndex = element.index;
                            const machineName = chartData.datasets[datasetIndex].label;
                            const dateStr = chartData.dates[dataIndex];
                            
                            // Could navigate to time records filtered by machine and date
                            console.log(`Machine: ${machineName}, Date: ${dateStr}`);
                        }
                    },
                },
            });
        }

        /**
         * Render user activity chart
         */
        function renderUserChart(chartData) {
            const ctx = document.getElementById('userActivityChart').getContext('2d');

            if (userChart) {
                userChart.destroy();
            }

            userChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets,
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Hours',
                            },
                        },
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.x.toFixed(2) + ' hours';
                                },
                            },
                        },
                    },
                    onClick: function(event, activeElements) {
                        if (activeElements.length > 0) {
                            const element = activeElements[0];
                            const datasetIndex = element.datasetIndex;
                            const dataIndex = element.index;
                            const userName = chartData.datasets[datasetIndex].label;
                            const dateStr = chartData.dates[dataIndex];
                            
                            // Could navigate to time records filtered by user and date
                            console.log(`User: ${userName}, Date: ${dateStr}`);
                        }
                    },
                },
            });
        }

        /**
         * Render activity detail table
         */
        function renderActivityTable(data) {
            const tbody = document.getElementById('activityTableBody');
            tbody.innerHTML = '';

            if (!data.detailedActivity || data.detailedActivity.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No activity found</td></tr>';
                return;
            }

            data.detailedActivity.forEach(activity => {
                const row = document.createElement('tr');
                row.className = 'table-row-hover';
                
                // Format date
                const activityDate = new Date(activity.date);
                const dateStr = activityDate.toLocaleDateString('de-DE');
                
                row.innerHTML = `
                    <td><small>${dateStr}</small></td>
                    <td>${activity.user}</td>
                    <td>${activity.machine}</td>
                    <td>
                        <span class="badge bg-info" data-bs-toggle="tooltip" title="${activity.project}">
                            ${activity.project.length > 20 ? activity.project.substring(0, 20) + '...' : activity.project}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-secondary" data-bs-toggle="tooltip" title="${activity.position}">
                            ${activity.position.length > 15 ? activity.position.substring(0, 15) + '...' : activity.position}
                        </span>
                    </td>
                    <td><strong>${activity.duration.toFixed(2)}</strong></td>
                    <td>
                        <a href="{{ route('admin.time.show', ['id' => 'PLACEHOLDER_ID']) }}".replace('PLACEHOLDER_ID', activity.id) class="btn btn-sm btn-outline-primary" title="View Details">
                            <i class="bi bi-eye"></i>
                        </a>
                    </td>
                `;
                
                tbody.appendChild(row);
            });

            // Initialize Bootstrap tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
    </script>
@endpush

@push('styles')
    <style>
        .table-row-hover:hover {
            background-color: #f8f9fa;
        }

        .card {
            transition: box-shadow 0.3s ease;
        }

        .card:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        }

        canvas {
            max-height: 300px;
        }

        .badge {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
        }
    </style>
@endpush
