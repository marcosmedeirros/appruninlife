<?php
require_once __DIR__ . '/includes/paths.php';
?>
<!DOCTYPE html>
<html lang="pt-BR" class="h-full bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestor Geral de Apostas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'inter': ['Inter', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body { font-family: 'Inter', sans-serif; }
        input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        .form-input::placeholder { color: #6b7280; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: #1f2937; }
        ::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #6b7280; }

        .nav-link {
            display: flex; align-items: center; padding: 0.75rem 1rem;
            border-radius: 0.375rem; font-weight: 500;
            transition: all 0.15s ease-in-out;
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-gray-900 text-gray-200">

    <aside class="flex-shrink-0 w-64 bg-gray-800 flex flex-col shadow-lg">
        <div class="h-16 flex items-center justify-center px-4 shadow-md bg-gray-900">
            <h1 class="text-xl font-semibold text-white">General Bets</h1>
        </div>
        <nav id="main-nav" class="flex-1 overflow-y-auto p-4 space-y-2">
            <a href="#" class="nav-link group" data-page="dashboard">
                <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3"></i>
                Dashboard
            </a>
            <a href="#" class="nav-link group" data-page="entradas">
                <i data-lucide="list" class="w-5 h-5 mr-3"></i>
                Minhas Apostas
            </a>
            <a href="#" class="nav-link group" data-page="competicoes">
                <i data-lucide="trophy" class="w-5 h-5 mr-3"></i>
                Competicoes
            </a>
            <a href="#" class="nav-link group" data-page="caixa">
                <i data-lucide="calendar-days" class="w-5 h-5 mr-3"></i>
                Caixa Diario
            </a>
        </nav>
    </aside>

    <main class="flex-1 overflow-y-auto p-8">
        <div id="page-dashboard" class="page-content space-y-8">
            <h2 class="text-3xl font-bold text-white">Dashboard Geral</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-gray-800 p-6 rounded-lg shadow-md border-l-4 border-indigo-500">
                    <h3 class="text-sm font-medium text-gray-400">Lucro Total</h3>
                    <p id="dash-lucro" class="mt-2 text-3xl font-bold text-white">R$ 0,00</p>
                </div>
                <div class="bg-gray-800 p-6 rounded-lg shadow-md">
                    <h3 class="text-sm font-medium text-gray-400">Total Investido</h3>
                    <p id="dash-valor" class="mt-2 text-3xl font-bold text-white">R$ 0,00</p>
                </div>
                <div class="bg-gray-800 p-6 rounded-lg shadow-md">
                    <h3 class="text-sm font-medium text-gray-400">Total de Entradas</h3>
                    <p id="dash-total" class="mt-2 text-3xl font-bold text-white">0</p>
                </div>
                <div class="bg-gray-800 p-6 rounded-lg shadow-md">
                    <h3 class="text-sm font-medium text-gray-400">Winrate</h3>
                    <p id="dash-winrate" class="mt-2 text-3xl font-bold text-white">0,0%</p>
                </div>
            </div>

            <div class="bg-gray-800 p-6 rounded-lg shadow-md">
                <h3 class="text-lg font-medium text-white mb-4">Evolucao da Banca</h3>
                <div id="chart-container" class="relative h-64 bg-gray-800 rounded-md">
                    <canvas id="bankrollChart"></canvas>
                    <p id="chart-empty" class="text-gray-400 absolute inset-0 flex items-center justify-center hidden">Adicione apostas em dias diferentes para gerar o grafico.</p>
                </div>
            </div>
        </div>

        <div id="page-entradas" class="page-content hidden space-y-6">
            <div class="flex justify-between items-center">
                <h2 class="text-3xl font-bold text-white">Registro de Apostas</h2>
                <button onclick="openModal()" class="flex items-center bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md shadow-sm transition-colors">
                    <i data-lucide="plus" class="w-5 h-5 mr-2"></i>
                    Nova Aposta
                </button>
            </div>

            <div class="bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Descricao / Selecao</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Resultado</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Odds</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Investido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Retorno</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Acoes</th>
                        </tr>
                    </thead>
                    <tbody id="apostas-table-body" class="divide-y divide-gray-700">
                        </tbody>
                </table>
                <div id="entradas-empty" class="hidden p-12 text-center text-gray-400">
                    <i data-lucide="clipboard-list" class="w-12 h-12 mx-auto mb-4 opacity-50"></i>
                    <p>Nenhuma aposta registrada.</p>
                </div>
            </div>
        </div>

        <div id="page-competicoes" class="page-content hidden space-y-6">
            <h2 class="text-3xl font-bold text-white">Performance por Competicao</h2>
            <div class="bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Competicao</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Qtd. Apostas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Lucro Liquido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Winrate</th>
                        </tr>
                    </thead>
                    <tbody id="competicoes-table-body" class="divide-y divide-gray-700"></tbody>
                </table>
                <div id="competicoes-empty" class="hidden p-8 text-center text-gray-400">
                    <p>Sem dados suficientes.</p>
                </div>
            </div>
        </div>

        <div id="page-caixa" class="page-content hidden space-y-6">
            <h2 class="text-3xl font-bold text-white">Fluxo de Caixa Diario</h2>
            <div class="bg-gray-800 rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Volume (Qtd)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Total Investido</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-300 uppercase">Resultado Dia</th>
                        </tr>
                    </thead>
                    <tbody id="caixa-table-body" class="divide-y divide-gray-700"></tbody>
                </table>
                 <div id="caixa-empty" class="hidden p-8 text-center text-gray-400">
                    <p>Nenhuma movimentacao registrada.</p>
                </div>
            </div>
        </div>
    </main>

    <div id="aposta-modal" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 hidden p-4 backdrop-blur-sm">
        <div class="bg-gray-800 rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto border border-gray-700">
            <form id="aposta-form" class="p-8 space-y-6">
                <div class="flex justify-between items-center pb-4 border-b border-gray-700">
                    <h3 id="modal-title" class="text-2xl font-bold text-white">Nova Aposta</h3>
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-white">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>

                <input type="hidden" id="aposta-id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="data" class="block text-sm font-medium text-gray-300 mb-1">Data do Evento</label>
                        <input type="date" id="data" class="form-input w-full bg-gray-900 border-gray-700 rounded-md text-white focus:ring-indigo-500" required>
                    </div>
                    <div>
                        <label for="num-selecoes" class="block text-sm font-medium text-gray-300 mb-1">Tipo de Aposta</label>
                        <select id="num-selecoes" class="form-input w-full bg-gray-900 border-gray-700 rounded-md text-white focus:ring-indigo-500" required onchange="toggleLegFields()">
                            <option value="1">Simples (1 Selecao)</option>
                            <option value="2">Dupla (2 Selecoes)</option>
                            <option value="3">Tripla+ (Multipla)</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-4 bg-gray-900 p-4 rounded-lg border border-gray-700">
                    <h4 class="text-sm font-semibold text-indigo-400 uppercase tracking-wide">Detalhes da Aposta</h4>

                    <div id="sel-1-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Competicao / Esporte</label>
                            <input type="text" id="comp1" class="form-input w-full bg-gray-800 border-gray-600 rounded text-sm" placeholder="Ex: Premier League" list="competicoes-list" required>
                        </div>
                         <div>
                            <label class="block text-xs text-gray-400 mb-1">Descricao / Mercado</label>
                            <input type="text" id="desc1" class="form-input w-full bg-gray-800 border-gray-600 rounded text-sm" placeholder="Ex: Arsenal para vencer" required>
                        </div>
                    </div>

                    <div id="sel-2-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4 hidden pt-4 border-t border-gray-800">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Competicao 2</label>
                            <input type="text" id="comp2" class="form-input w-full bg-gray-800 border-gray-600 rounded text-sm" placeholder="Ex: NBA" list="competicoes-list">
                        </div>
                         <div>
                            <label class="block text-xs text-gray-400 mb-1">Descricao 2</label>
                            <input type="text" id="desc2" class="form-input w-full bg-gray-800 border-gray-600 rounded text-sm" placeholder="Ex: Lakers +5.5">
                        </div>
                    </div>

                    <div id="sel-3-fields" class="grid grid-cols-1 sm:grid-cols-2 gap-4 hidden pt-4 border-t border-gray-800">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Competicao 3</label>
                            <input type="text" id="comp3" class="form-input w-full bg-gray-800 border-gray-600 rounded text-sm" placeholder="Ex: UFC" list="competicoes-list">
                        </div>
                         <div>
                            <label class="block text-xs text-gray-400 mb-1">Descricao 3</label>
                            <input type="text" id="desc3" class="form-input w-full bg-gray-800 border-gray-600 rounded text-sm" placeholder="Ex: Poatan KO">
                        </div>
                    </div>
                </div>

                <datalist id="competicoes-list"></datalist>

                <div class="pt-2">
                    <h4 class="text-sm font-semibold text-white mb-4 border-b border-gray-700 pb-2">Financeiro & Resultado</h4>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <label for="odds" class="block text-xs text-gray-400">Odds Totais</label>
                            <input type="number" step="0.01" id="odds" class="form-input mt-1 w-full bg-gray-900 border-gray-700 rounded text-white" placeholder="1.90" required>
                        </div>
                        <div>
                            <label for="valor" class="block text-xs text-gray-400">Valor (R$)</label>
                            <input type="number" step="0.01" id="valor" class="form-input mt-1 w-full bg-gray-900 border-gray-700 rounded text-white" placeholder="50.00" required>
                        </div>
                        <div>
                            <label for="gr" class="block text-xs text-gray-400">Status</label>
                            <select id="gr" class="form-input mt-1 w-full bg-gray-900 border-gray-700 rounded text-white" required>
                                <option value="Green">Green</option>
                                <option value="Red">Red</option>
                                <option value="Void">Anulada</option>
                            </select>
                        </div>
                        <div>
                            <label for="lucro" class="block text-xs text-gray-400">Lucro/Prejuizo</label>
                            <input type="number" step="0.01" id="lucro" class="form-input mt-1 w-full bg-gray-900 border-gray-700 rounded text-white font-bold" placeholder="0.00" required>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-6 border-t border-gray-700">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-700 text-gray-200 rounded-md hover:bg-gray-600 transition-colors">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-500/30">Salvar Aposta</button>
                </div>
            </form>
        </div>
    </div>

    <div id="confirm-modal" class="fixed inset-0 bg-black bg-opacity-80 flex items-center justify-center z-50 hidden p-4 backdrop-blur-sm">
        <div class="bg-gray-800 rounded-lg shadow-xl w-full max-w-sm border border-gray-700 p-6 text-center">
            <div class="w-12 h-12 bg-red-900/50 rounded-full flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-triangle" class="text-red-500 w-6 h-6"></i>
            </div>
            <h3 class="text-lg font-bold text-white mb-2">Excluir Registro?</h3>
            <p class="text-sm text-gray-400 mb-6">Essa acao e irreversivel.</p>
            <div class="flex justify-center space-x-3">
                <button onclick="closeConfirmModal()" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">Cancelar</button>
                <button id="confirm-delete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Sim, Excluir</button>
            </div>
        </div>
    </div>

    <script>
        // Calcular base path dinamicamente
        const getBasePath = () => {
            const pathname = window.location.pathname;
            if (pathname.includes('/app')) {
                return pathname.substring(0, pathname.indexOf('/app'));
            }
            return '';
        };

        const BASE_PATH = getBasePath();
        const API_URL = BASE_PATH + '/api.php';

        // --- 1. Estado Global ---
        let db = { apostas: [] };
        let editId = null;
        let deleteCallback = null;
        let bankrollChartInstance = null;

        // Mobile Menu Toggle
        const toggleMenu = () => {
            const sidebar = document.getElementById('sidebar');
            sidebar.classList.toggle('-translate-x-full');
        };

        // Configurar botão de toggle
        document.getElementById('menu-toggle')?.addEventListener('click', toggleMenu);

        // Fechar menu ao clicar em um link de navegação (mobile)
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                document.querySelectorAll('.nav-link').forEach(link => {
                    link.addEventListener('click', () => {
                        if (window.innerWidth < 768) {
                            document.getElementById('sidebar').classList.add('-translate-x-full');
                        }
                    });
                });
            }, 100);
        });

        // --- 2. Funcoes Utilitarias ---
        const formatCurrency = (val) => val.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
        const formatDate = (iso) => iso ? new Date(iso).toLocaleDateString('pt-BR', { timeZone: 'UTC' }) : '-';

        const apiRequest = async (action, payload = null) => {
            const opts = payload ? {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            } : { method: 'GET' };
            const res = await fetch(`${API_URL}?action=${encodeURIComponent(action)}`, opts);
            if (!res.ok) {
                throw new Error(`Erro na API (${action}): ${res.status}`);
            }
            return res.json();
        };

        const handleApiError = (err) => {
            console.error(err);
            alert('Nao foi possivel carregar os dados do banco.');
        };

        // --- 3. Core Logic ---
        const initializeApp = async () => {
            await loadData();

            // Navegacao
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    navigateTo(link.dataset.page);
                });
            });

            // Formulario
            document.getElementById('aposta-form').addEventListener('submit', handleFormSubmit);

            // Automacao de calculo
            ['odds', 'valor', 'gr'].forEach(id => {
                document.getElementById(id).addEventListener('input', calculateLucro);
            });

            // Delete
            document.getElementById('confirm-delete').addEventListener('click', () => deleteCallback && deleteCallback());

            // Inicio
            navigateTo('dashboard');
        };

        const navigateTo = (page) => {
            // UI Toggle
            document.querySelectorAll('.page-content').forEach(el => el.classList.add('hidden'));
            document.getElementById(`page-${page}`).classList.remove('hidden');

            // Menu Style
            document.querySelectorAll('.nav-link').forEach(el => {
                const isActive = el.dataset.page === page;
                el.classList.toggle('bg-indigo-600', isActive);
                el.classList.toggle('text-white', isActive);
                el.classList.toggle('text-gray-400', !isActive);
                el.classList.toggle('hover:bg-gray-800', !isActive);
            });

            // Render
            if (page === 'dashboard') renderDashboard();
            if (page === 'entradas') renderEntradas();
            if (page === 'competicoes') renderCompeticoes();
            if (page === 'caixa') renderCaixa();
        };

        // --- 4. Renderers ---

        const renderDashboard = () => {
            const totalVal = db.apostas.reduce((acc, a) => acc + a.valor, 0);
            const totalLucro = db.apostas.reduce((acc, a) => acc + a.lucro, 0);
            const totalBets = db.apostas.length;
            const greens = db.apostas.filter(a => a.gr === 'Green').length;
            const winrate = totalBets > 0 ? (greens / totalBets) * 100 : 0;

            document.getElementById('dash-lucro').innerText = formatCurrency(totalLucro);
            document.getElementById('dash-valor').innerText = formatCurrency(totalVal);
            document.getElementById('dash-total').innerText = totalBets;
            document.getElementById('dash-winrate').innerText = winrate.toFixed(1) + '%';

            // Cores
            const lEl = document.getElementById('dash-lucro');
            lEl.className = `mt-2 text-3xl font-bold ${totalLucro >= 0 ? 'text-green-400' : 'text-red-400'}`;

            renderChart();
        };

        const renderChart = () => {
            const ctx = document.getElementById('bankrollChart').getContext('2d');
            if (bankrollChartInstance) bankrollChartInstance.destroy();

            // Agrupa lucro por dia
            const daily = db.apostas.reduce((acc, a) => {
                acc[a.data] = (acc[a.data] || 0) + a.lucro;
                return acc;
            }, {});

            const days = Object.keys(daily).sort((a,b) => new Date(a) - new Date(b));

            if (days.length < 2) {
                document.getElementById('bankrollChart').classList.add('hidden');
                document.getElementById('chart-empty').classList.remove('hidden');
                return;
            }

            document.getElementById('bankrollChart').classList.remove('hidden');
            document.getElementById('chart-empty').classList.add('hidden');

            let accumulated = 0;
            const dataPoints = days.map(day => {
                accumulated += daily[day];
                return accumulated;
            });

            bankrollChartInstance = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: days.map(formatDate),
                    datasets: [{
                        label: 'Evolucao da Banca',
                        data: dataPoints,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: '#374151' }, ticks: { color: '#9ca3af' } },
                        y: { grid: { color: '#374151' }, ticks: { color: '#9ca3af' } }
                    }
                }
            });
        };

        const renderEntradas = () => {
            const tbody = document.getElementById('apostas-table-body');
            tbody.innerHTML = '';

            if (!db.apostas.length) {
                document.getElementById('entradas-empty').classList.remove('hidden');
                return;
            }
            document.getElementById('entradas-empty').classList.add('hidden');

            const sorted = [...db.apostas].sort((a,b) => new Date(b.data) - new Date(a.data));

            sorted.forEach(a => {
                // Monta descricao visual das selecoes
                const descHtml = a.selecoes.map(s =>
                    `<div class="text-sm"><span class="text-indigo-400 font-semibold text-xs">[${s.comp}]</span> <span class="text-white">${s.desc}</span></div>`
                ).join('');

                const tipoLabel = a.selecoes.length > 1
                    ? `<span class="px-2 py-1 bg-purple-900 text-purple-200 rounded text-xs">Multipla (${a.selecoes.length})</span>`
                    : `<span class="px-2 py-1 bg-gray-700 text-gray-300 rounded text-xs">Simples</span>`;

                const grColor = a.gr === 'Green' ? 'text-green-400' : (a.gr === 'Red' ? 'text-red-400' : 'text-gray-400');
                const lucroColor = a.lucro > 0 ? 'text-green-400' : (a.lucro < 0 ? 'text-red-400' : 'text-gray-400');

                const tr = document.createElement('tr');
                tr.className = 'bg-gray-800 hover:bg-gray-750 border-b border-gray-700';
                tr.innerHTML = `
                    <td class="px-3 md:px-6 py-4 text-gray-300 whitespace-nowrap text-xs md:text-sm">${formatDate(a.data)}</td>
                    <td class="px-3 md:px-6 py-4 text-xs md:text-sm">${descHtml}</td>
                    <td class="px-3 md:px-6 py-4">${tipoLabel}</td>
                    <td class="px-3 md:px-6 py-4 font-bold ${grColor} text-xs md:text-sm">${a.gr}</td>
                    <td class="px-3 md:px-6 py-4 text-gray-300 text-xs md:text-sm">${a.odds.toFixed(2)}</td>
                    <td class="px-3 md:px-6 py-4 text-gray-300 text-xs md:text-sm">${formatCurrency(a.valor)}</td>
                    <td class="px-3 md:px-6 py-4 font-bold ${lucroColor} text-xs md:text-sm">${formatCurrency(a.lucro)}</td>
                    <td class="px-3 md:px-6 py-4 space-x-2 md:space-x-3">
                        <button onclick="openModal('${a.id}')" class="text-indigo-400 hover:text-indigo-300 text-xs md:text-sm font-medium">Editar</button>
                        <button onclick="askDelete('${a.id}')" class="text-red-400 hover:text-red-300 text-xs md:text-sm font-medium">Excluir</button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        };

        const renderCompeticoes = () => {
            const tbody = document.getElementById('competicoes-table-body');
            tbody.innerHTML = '';

            // Agrupa por competicao
            const stats = {};
            db.apostas.forEach(a => {
                a.selecoes.forEach(s => {
                    const comp = s.comp || 'Outros';
                    if (!stats[comp]) stats[comp] = { lucro: 0, count: 0, green: 0 };

                    // Atribui o lucro total para a competicao para visao simples
                    stats[comp].lucro += a.lucro;
                    stats[comp].count++;
                    if (a.gr === 'Green') stats[comp].green++;
                });
            });

            const list = Object.entries(stats).sort((a,b) => b[1].lucro - a[1].lucro);

            if(!list.length) {
                document.getElementById('competicoes-empty').classList.remove('hidden');
                return;
            }
            document.getElementById('competicoes-empty').classList.add('hidden');

            list.forEach(([nome, st]) => {
                const wr = (st.green / st.count) * 100;
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-3 md:px-6 py-4 text-white font-medium text-xs md:text-sm">${nome}</td>
                    <td class="px-3 md:px-6 py-4 text-gray-300 text-xs md:text-sm">${st.count}</td>
                    <td class="px-3 md:px-6 py-4 ${st.lucro >= 0 ? 'text-green-400' : 'text-red-400'} font-bold text-xs md:text-sm">${formatCurrency(st.lucro)}</td>
                    <td class="px-3 md:px-6 py-4 text-gray-300 text-xs md:text-sm">${wr.toFixed(1)}%</td>
                `;
                tbody.appendChild(tr);
            });
        };

        const renderCaixa = () => {
            const tbody = document.getElementById('caixa-table-body');
            tbody.innerHTML = '';

            const stats = db.apostas.reduce((acc, a) => {
                if (!acc[a.data]) acc[a.data] = { val: 0, luc: 0, qtd: 0 };
                acc[a.data].val += a.valor;
                acc[a.data].luc += a.lucro;
                acc[a.data].qtd++;
                return acc;
            }, {});

            const list = Object.entries(stats).sort((a,b) => new Date(b[0]) - new Date(a[0]));

            if(!list.length) {
                document.getElementById('caixa-empty').classList.remove('hidden');
                return;
            }
            document.getElementById('caixa-empty').classList.add('hidden');

            list.forEach(([data, st]) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="px-3 md:px-6 py-4 text-white text-xs md:text-sm">${formatDate(data)}</td>
                    <td class="px-3 md:px-6 py-4 text-gray-300 text-xs md:text-sm">${st.qtd}</td>
                    <td class="px-3 md:px-6 py-4 text-gray-300 text-xs md:text-sm">${formatCurrency(st.val)}</td>
                    <td class="px-3 md:px-6 py-4 ${st.luc >= 0 ? 'text-green-400' : 'text-red-400'} font-bold text-xs md:text-sm">${formatCurrency(st.luc)}</td>
                `;
                tbody.appendChild(tr);
            });
        };

        // --- 5. Form Logic ---

        const toggleLegFields = () => {
            const n = parseInt(document.getElementById('num-selecoes').value);
            document.getElementById('sel-2-fields').classList.toggle('hidden', n < 2);
            document.getElementById('comp2').required = n >= 2;
            document.getElementById('desc2').required = n >= 2;

            document.getElementById('sel-3-fields').classList.toggle('hidden', n < 3);
            document.getElementById('comp3').required = n >= 3;
            document.getElementById('desc3').required = n >= 3;
        };

        const calculateLucro = () => {
            const odds = parseFloat(document.getElementById('odds').value) || 0;
            const val = parseFloat(document.getElementById('valor').value) || 0;
            const status = document.getElementById('gr').value;
            const field = document.getElementById('lucro');

            if (status === 'Green') field.value = ((odds * val) - val).toFixed(2);
            else if (status === 'Red') field.value = (-val).toFixed(2);
            else field.value = (0).toFixed(2); // Void
        };

        const openModal = (id = null) => {
            const form = document.getElementById('aposta-form');
            form.reset();
            editId = id;

            // Popula Datalist de Competicoes
            const dl = document.getElementById('competicoes-list');
            dl.innerHTML = '';
            const comps = [...new Set(db.apostas.flatMap(a => a.selecoes.map(s => s.comp)))].sort();
            comps.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c;
                dl.appendChild(opt);
            });

            if (id) {
                const a = db.apostas.find(x => x.id === id);
                document.getElementById('aposta-id').value = a.id;
                document.getElementById('data').value = a.data;
                document.getElementById('odds').value = a.odds;
                document.getElementById('valor').value = a.valor;
                document.getElementById('gr').value = a.gr;
                document.getElementById('lucro').value = a.lucro;

                // Popula selecoes
                const len = a.selecoes.length;
                document.getElementById('num-selecoes').value = len > 3 ? 3 : len; // Simplificacao
                toggleLegFields();

                if(a.selecoes[0]) { document.getElementById('comp1').value = a.selecoes[0].comp; document.getElementById('desc1').value = a.selecoes[0].desc; }
                if(a.selecoes[1]) { document.getElementById('comp2').value = a.selecoes[1].comp; document.getElementById('desc2').value = a.selecoes[1].desc; }
                if(a.selecoes[2]) { document.getElementById('comp3').value = a.selecoes[2].comp; document.getElementById('desc3').value = a.selecoes[2].desc; }

                document.getElementById('modal-title').innerText = 'Editar Aposta';
            } else {
                document.getElementById('data').value = new Date().toISOString().split('T')[0];
                document.getElementById('num-selecoes').value = '1';
                toggleLegFields();
                document.getElementById('modal-title').innerText = 'Nova Aposta';
            }

            document.getElementById('aposta-modal').classList.remove('hidden');
        };

        const closeModal = () => document.getElementById('aposta-modal').classList.add('hidden');
        const closeConfirmModal = () => document.getElementById('confirm-modal').classList.add('hidden');

        const askDelete = (id) => {
            deleteCallback = async () => {
                try {
                    await apiRequest('delete', { id });
                    await loadData();
                    closeConfirmModal();
                    const activePage = document.querySelector('.nav-link.bg-indigo-600')?.dataset.page || 'dashboard';
                    navigateTo(activePage);
                } catch (err) {
                    handleApiError(err);
                }
            };
            document.getElementById('confirm-modal').classList.remove('hidden');
        };

        const handleFormSubmit = async (e) => {
            e.preventDefault();

            const n = parseInt(document.getElementById('num-selecoes').value);
            const selecoes = [];

            for(let i=1; i<=n; i++) {
                // Se n=3, pega 1, 2 e 3
                const c = document.getElementById(`comp${i}`).value;
                const d = document.getElementById(`desc${i}`).value;
                if(c && d) selecoes.push({ comp: c, desc: d });
            }

            const payload = {
                id: document.getElementById('aposta-id').value || null,
                data: document.getElementById('data').value,
                odds: parseFloat(document.getElementById('odds').value),
                valor: parseFloat(document.getElementById('valor').value),
                gr: document.getElementById('gr').value,
                lucro: parseFloat(document.getElementById('lucro').value),
                selecoes: selecoes
            };

            try {
                if (editId) {
                    payload.id = editId;
                    await apiRequest('update', payload);
                } else {
                    await apiRequest('create', payload);
                }
                await loadData();
            } catch (err) {
                handleApiError(err);
            }

            closeModal();

            // Refresh na pagina atual
            const activePage = document.querySelector('.nav-link.bg-indigo-600')?.dataset.page || 'dashboard';
            navigateTo(activePage);
        };

        // --- 6. Persistencia ---
        const loadData = async () => {
            try {
                const data = await apiRequest('list');
                db.apostas = (data.apostas || []).map(a => ({
                    ...a,
                    id: String(a.id),
                    odds: parseFloat(a.odds) || 0,
                    valor: parseFloat(a.valor) || 0,
                    lucro: parseFloat(a.lucro) || 0,
                    selecoes: a.selecoes || []
                }));
            } catch (err) {
                db.apostas = [];
                handleApiError(err);
            }
        };

        // Boot
        initializeApp();
    </script>

    <script src="https://unpkg.com/lucide@latest"></script>
    <script>lucide.createIcons();</script>
</body>
</html>

