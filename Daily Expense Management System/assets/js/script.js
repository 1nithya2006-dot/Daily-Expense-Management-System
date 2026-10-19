/* ============================================================
   assets/js/script.js  -  Daily Expense Management System
   ============================================================ */

'use strict';

// ---- Theme Toggle ----
(function initTheme() {
  const saved = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);
  updateThemeIcons(saved);
})();

function updateThemeIcons(theme) {
  const sun  = document.getElementById('iconSun');
  const moon = document.getElementById('iconMoon');
  if (!sun || !moon) return;
  if (theme === 'dark') { sun.style.display = 'none'; moon.style.display = ''; }
  else                  { sun.style.display = '';     moon.style.display = 'none'; }
}

document.addEventListener('DOMContentLoaded', function () {
  // Theme toggle
  const themeBtn = document.getElementById('themeToggle');
  if (themeBtn) {
    themeBtn.addEventListener('click', function () {
      const current = document.documentElement.getAttribute('data-theme') || 'light';
      const next = current === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', next);
      localStorage.setItem('theme', next);
      updateThemeIcons(next);
    });
  }

  // Sidebar toggle
  const sidebarToggle  = document.getElementById('sidebarToggle');
  const sidebarClose   = document.getElementById('sidebarClose');
  const sidebar        = document.getElementById('sidebar');
  const overlay        = document.getElementById('sidebarOverlay');

  function openSidebar()  { sidebar?.classList.add('open'); overlay?.classList.add('open'); }
  function closeSidebar() { sidebar?.classList.remove('open'); overlay?.classList.remove('open'); }

  sidebarToggle?.addEventListener('click', openSidebar);
  sidebarClose?.addEventListener('click',  closeSidebar);
  overlay?.addEventListener('click',       closeSidebar);

  // Flash messages as toasts
  const flashSuccess = document.querySelector('.alert-success');
  const flashError   = document.querySelector('.alert-error');
  if (flashSuccess) showToast(flashSuccess.textContent.trim(), 'success');
  if (flashError)   showToast(flashError.textContent.trim(),   'error');

  // Password toggle
  document.querySelectorAll('.toggle-password').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const input = btn.closest('.input-password-wrapper')?.querySelector('input');
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
    });
  });

  // Form validation
  const expenseForm  = document.getElementById('expenseForm');
  const registerForm = document.getElementById('registerForm');
  const loginForm    = document.getElementById('loginForm');

  if (expenseForm) {
    expenseForm.addEventListener('submit', function (e) {
      const title  = expenseForm.querySelector('[name="title"]');
      const amount = expenseForm.querySelector('[name="amount"]');
      const cat    = expenseForm.querySelector('[name="category"]');
      const method = expenseForm.querySelector('[name="payment_method"]');
      const date   = expenseForm.querySelector('[name="expense_date"]');
      let ok = true;

      [title, amount, cat, method, date].forEach(function (el) { clearError(el); });

      if (!title.value.trim())                   { markError(title, 'Title is required'); ok = false; }
      if (!amount.value || parseFloat(amount.value) <= 0) { markError(amount, 'Enter a valid positive amount'); ok = false; }
      if (!cat.value)                            { markError(cat, 'Select a category'); ok = false; }
      if (!method.value)                         { markError(method, 'Select a payment method'); ok = false; }
      if (!date.value)                           { markError(date, 'Select a date'); ok = false; }
      if (!ok) e.preventDefault();
    });
  }

  if (registerForm) {
    registerForm.addEventListener('submit', function (e) {
      const pass    = registerForm.querySelector('[name="password"]');
      const confirm = registerForm.querySelector('[name="confirm"]');
      clearError(pass); clearError(confirm);
      let ok = true;
      if (pass.value.length < 6) { markError(pass, 'At least 6 characters required'); ok = false; }
      if (pass.value !== confirm.value) { markError(confirm, 'Passwords do not match'); ok = false; }
      if (!ok) e.preventDefault();
    });
  }

  // ---- Charts ----
  if (typeof Chart !== 'undefined') {
    const chartColors = [
      '#22c55e','#3b82f6','#8b5cf6','#f59e0b','#ef4444','#14b8a6','#f97316','#6366f1','#ec4899'
    ];
    const isDark = () => document.documentElement.getAttribute('data-theme') === 'dark';
    const gridColor = () => isDark() ? 'rgba(255,255,255,.1)' : 'rgba(0,0,0,.07)';
    const textColor = () => isDark() ? '#94a3b8' : '#64748b';

    function baseScaleOpts() {
      return {
        grid: { color: gridColor() },
        ticks: { color: textColor(), font: { size: 11 } }
      };
    }

    // Daily bar chart
    if (typeof dailyLabels !== 'undefined' && document.getElementById('dailyChart')) {
      new Chart(document.getElementById('dailyChart'), {
        type: 'bar',
        data: {
          labels: dailyLabels,
          datasets: [{
            label: 'Expenses (₹)',
            data: dailyData,
            backgroundColor: 'rgba(34,197,94,.7)',
            borderColor: '#22c55e',
            borderWidth: 1.5,
            borderRadius: 6
          }]
        },
        options: {
          responsive: true,
          plugins: { legend: { display: false } },
          scales: { x: baseScaleOpts(), y: { ...baseScaleOpts(), beginAtZero: true } }
        }
      });
    }

    // Category doughnut
    if (typeof catLabels !== 'undefined' && catLabels.length && document.getElementById('categoryChart')) {
      new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
          labels: catLabels,
          datasets: [{ data: catData, backgroundColor: chartColors, borderWidth: 2, borderColor: isDark() ? '#1e293b' : '#fff' }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom', labels: { color: textColor(), font: { size: 11 }, padding: 12 } }
          }
        }
      });
    }

    // Monthly comparison bar chart
    if (typeof monthLabels !== 'undefined' && document.getElementById('monthlyChart')) {
      new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
          labels: monthLabels,
          datasets: [
            { label: 'Income (₹)',   data: monthIncData, backgroundColor: 'rgba(34,197,94,.7)',  borderColor: '#22c55e', borderWidth: 1.5, borderRadius: 6 },
            { label: 'Expenses (₹)', data: monthExpData, backgroundColor: 'rgba(239,68,68,.7)', borderColor: '#ef4444', borderWidth: 1.5, borderRadius: 6 }
          ]
        },
        options: {
          responsive: true,
          plugins: { legend: { labels: { color: textColor() } } },
          scales: { x: baseScaleOpts(), y: { ...baseScaleOpts(), beginAtZero: true } }
        }
      });
    }

    // Statistics page extras
    if (typeof PAGE !== 'undefined' && PAGE === 'statistics') {
      // Weekly chart
      if (typeof wkLabels !== 'undefined' && wkLabels.length && document.getElementById('weeklyChart')) {
        new Chart(document.getElementById('weeklyChart'), {
          type: 'bar',
          data: {
            labels: wkLabels,
            datasets: [{ label: 'Expenses (₹)', data: wkData, backgroundColor: 'rgba(99,102,241,.7)', borderColor: '#6366f1', borderWidth: 1.5, borderRadius: 6 }]
          },
          options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { x: baseScaleOpts(), y: { ...baseScaleOpts(), beginAtZero: true } }
          }
        });
      }
      // Method pie
      if (typeof methLabels !== 'undefined' && methLabels.length && document.getElementById('methodChart')) {
        new Chart(document.getElementById('methodChart'), {
          type: 'pie',
          data: {
            labels: methLabels,
            datasets: [{ data: methData, backgroundColor: ['#22c55e','#3b82f6','#f59e0b','#8b5cf6'], borderWidth: 2 }]
          },
          options: {
            responsive: true,
            plugins: { legend: { position: 'bottom', labels: { color: textColor(), font: { size: 11 }, padding: 12 } } }
          }
        });
      }
    }
  }
});

// ---- Toast ----
function showToast(msg, type = 'success') {
  const container = document.getElementById('toast-container');
  if (!container || !msg) return;
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  const icon = type === 'success' ? '✓' : type === 'error' ? '✕' : '!';
  toast.innerHTML = `<span style="font-weight:700">${icon}</span><span>${msg}</span>`;
  container.appendChild(toast);
  setTimeout(function () {
    toast.classList.add('toast-fade-out');
    setTimeout(function () { toast.remove(); }, 350);
  }, 3500);
}

// ---- Form validation helpers ----
function markError(el, msg) {
  el.style.borderColor = '#ef4444';
  let err = el.parentElement.querySelector('.field-error');
  if (!err) {
    err = document.createElement('p');
    err.className = 'field-error';
    err.style.cssText = 'color:#ef4444;font-size:.78rem;margin-top:4px';
    el.parentElement.appendChild(err);
  }
  err.textContent = msg;
}
function clearError(el) {
  el.style.borderColor = '';
  const err = el.parentElement?.querySelector('.field-error');
  if (err) err.remove();
}

// ---- Expense History: Edit Modal ----
function openEditModal(btn) {
  document.getElementById('editId').value       = btn.dataset.id;
  document.getElementById('editTitle').value    = btn.dataset.title;
  document.getElementById('editAmount').value   = btn.dataset.amount;
  document.getElementById('editDate').value     = btn.dataset.date;
  document.getElementById('editNotes').value    = btn.dataset.notes;
  const catSel = document.getElementById('editCategory');
  const methSel = document.getElementById('editMethod');
  if (catSel)  setSelectValue(catSel,  btn.dataset.category);
  if (methSel) setSelectValue(methSel, btn.dataset.method);
  document.getElementById('editModal').style.display = 'flex';
}
function closeEditModal() {
  document.getElementById('editModal').style.display = 'none';
}
function setSelectValue(sel, val) {
  for (let i = 0; i < sel.options.length; i++) {
    if (sel.options[i].value === val) { sel.selectedIndex = i; break; }
  }
}

// ---- Delete Expense ----
function deleteExpense(btn, csrfToken) {
  const id = btn.dataset.id;
  if (!confirm('Delete this expense? This cannot be undone.')) return;
  const form = new FormData();
  form.append('id', id);
  form.append('csrf_token', csrfToken);
  fetch('/daily-expense-management/actions/delete-expense.php', { method: 'POST', body: form })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        btn.closest('tr')?.remove();
        showToast('Expense deleted.', 'success');
      } else {
        showToast(data.message || 'Failed to delete.', 'error');
      }
    })
    .catch(function () { showToast('Network error.', 'error'); });
}

// ---- Income Modals ----
function openAddIncomeModal() { document.getElementById('addIncomeModal').style.display = 'flex'; }
function openEditIncomeModal(btn) {
  document.getElementById('editIncomeId').value       = btn.dataset.id;
  document.getElementById('editIncomeTitle').value    = btn.dataset.title;
  document.getElementById('editIncomeAmount').value   = btn.dataset.amount;
  document.getElementById('editIncomeDate').value     = btn.dataset.date;
  document.getElementById('editIncomeNotes').value    = btn.dataset.notes;
  const catSel = document.getElementById('editIncomeCategory');
  if (catSel) setSelectValue(catSel, btn.dataset.category);
  document.getElementById('editIncomeModal').style.display = 'flex';
}
function deleteIncome(btn, csrfToken) {
  const id = btn.dataset.id;
  if (!confirm('Delete this income record?')) return;
  const form = new FormData();
  form.append('id', id);
  form.append('csrf_token', csrfToken);
  fetch('/daily-expense-management/actions/delete-income.php', { method: 'POST', body: form })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) { btn.closest('tr')?.remove(); showToast('Income deleted.', 'success'); }
      else              { showToast(data.message || 'Failed.', 'error'); }
    })
    .catch(function () { showToast('Network error.', 'error'); });
}
function closeModal(id) { document.getElementById(id).style.display = 'none'; }

// Close modals on backdrop click
document.addEventListener('click', function (e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.style.display = 'none';
  }
});
