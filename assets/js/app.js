const POINTS = {
  task: 10,
  goal: 50,
  workout: 20,
  habit: 5,
};

const authSection = document.getElementById("auth");
const appSection = document.getElementById("app");
const loginForm = document.getElementById("login-form");
const registerForm = document.getElementById("register-form");
const welcome = document.getElementById("welcome");
const pointsEl = document.getElementById("points");
const levelEl = document.getElementById("level");

const views = [
  "dashboard",
  "tasks",
  "goals",
  "habits",
  "workouts",
  "categories",
  "missions",
  "achievements",
  "ranking",
  "profile",
  "admin",
  "history",
];

const state = {
  user: null,
  data: null,
  admin: null,
};

const formatDate = (value) =>
  value
    ? new Date(value).toLocaleString("pt-BR", { dateStyle: "short", timeStyle: "short" })
    : "Sem data";

const todayKey = () => new Date().toISOString().split("T")[0];

const uid = () => Math.random().toString(36).slice(2, 10);

const apiRequest = async (action, payload) => {
  const response = await fetch(`api.php?action=${action}`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify(payload ?? {}),
  });
  return response.json();
};

const fetchData = async () => {
  const response = await fetch("api.php?action=get");
  return response.json();
};

const addLog = (data, { title, points, type }) => {
  data.logs.unshift({
    id: uid(),
    title,
    points,
    type,
    date: new Date().toISOString(),
  });
};

const addPoints = (data, value, title, type) => {
  data.points += value;
  addLog(data, { title, points: value, type });
};

const calcLevel = (points) => Math.floor(points / 100) + 1;

const progressToNextLevel = (points) => ((points % 100) / 100) * 100;

const showMessage = (key, message) => {
  const el = document.querySelector(`[data-message="${key}"]`);
  if (el) {
    el.textContent = message;
  }
};

const resetMessages = () => {
  document.querySelectorAll(".form-message").forEach((el) => (el.textContent = ""));
};

const showView = (viewId) => {
  views.forEach((id) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.toggle("hidden", id !== viewId);
  });
  document.querySelectorAll(".nav-link").forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.view === viewId);
  });
};

const renderHeader = () => {
  if (!state.user || !state.data) return;
  welcome.textContent = `Olá, ${state.user.name}`;
  pointsEl.textContent = state.data.points;
  levelEl.textContent = calcLevel(state.data.points);
};

const renderDashboard = () => {
  const el = document.getElementById("dashboard");
  const data = state.data;
  const nextLevel = progressToNextLevel(data.points).toFixed(0);

  el.innerHTML = `
    <div class="dashboard-hero">
      <div class="hero-card">
        <h3>Seu progresso</h3>
        <p class="muted">Transforme sua rotina em conquistas diárias.</p>
        <strong style="font-size:2.4rem;">${data.points}</strong>
        <div class="progress"><span style="width:${nextLevel}%"></span></div>
        <p class="muted">${nextLevel}% para o próximo nível</p>
      </div>
      <div class="card">
        <h3>Checklist de hoje</h3>
        <p class="muted">${data.tasks.filter((task) => !task.completed).length} tarefas pendentes</p>
        <p class="muted">${data.habits.length} hábitos em foco</p>
      </div>
    </div>
    <div class="card-grid">
      <div class="card">
        <h3>Agenda de hoje</h3>
        <p class="muted">${data.tasks.filter((task) => !task.completed).length} tarefas ativas</p>
      </div>
      <div class="card">
        <h3>Metas em andamento</h3>
  <p class="muted">${data.goals.filter((goal) => !goal.completed).length} metas abertas</p>
      </div>
      <div class="card">
        <h3>Treinos programados</h3>
  <p class="muted">${data.workouts.filter((workout) => !workout.completed).length} treinos pendentes</p>
      </div>
      <div class="card">
        <h3>Hábitos</h3>
  <p class="muted">${data.habits.length} hábitos monitorados</p>
      </div>
    </div>
  `;
};

const renderTasks = () => {
  const el = document.getElementById("tasks");
  const data = state.data;
  const items = data.tasks
    .slice()
    .sort((a, b) => new Date(a.date || 0) - new Date(b.date || 0));

  el.innerHTML = `
    <div class="split">
      <div class="card">
        <h3>Nova atividade / reunião</h3>
        <form id="task-form" class="form">
          <label>
            Título
            <input type="text" name="title" required />
          </label>
          <label>
            Tipo
            <select name="type">
              <option value="Atividade diária">Atividade diária</option>
              <option value="Reunião">Reunião</option>
              <option value="Projeto">Projeto</option>
            </select>
          </label>
          <label>
            Categoria
            <select name="category">
              ${data.categories.map((cat) => `<option value="${cat.id}">${cat.name}</option>`).join("")}
            </select>
          </label>
          <label>
            Data e hora
            <input type="datetime-local" name="date" />
          </label>
          <button class="btn" type="submit">Adicionar</button>
        </form>
      </div>
      <div class="card">
        <h3>Agenda</h3>
        <div class="list">
          ${
            items.length
              ? items
                  .map(
                    (task) => `
              <div class="list-item">
                <div class="list-row">
                  <strong>${task.title}</strong>
                  <span class="tag">${task.type}</span>
                </div>
                <p class="muted">${formatDate(task.date)}</p>
                <div class="list-row">
                  <span class="muted">${getCategoryName(task.categoryId)}</span>
                  <div>
                    <button class="btn ghost" data-action="complete-task" data-id="${task.id}" ${
                      task.completed ? "disabled" : ""
                    }>Concluir +${POINTS.task}</button>
                    <button class="btn ghost" data-action="delete-task" data-id="${task.id}">Excluir</button>
                  </div>
                </div>
              </div>
            `
                  )
                  .join("")
              : `<p class="muted">Nenhuma atividade cadastrada.</p>`
          }
        </div>
      </div>
    </div>
  `;

  const form = document.getElementById("task-form");
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(form);
    const task = {
      id: uid(),
      title: formData.get("title"),
      type: formData.get("type"),
      categoryId: formData.get("category"),
      date: formData.get("date"),
      completed: false,
    };
  data.tasks.push(task);
  saveData();
    renderTasks();
    renderDashboard();
  });

  wireActions(el);
};

const renderGoals = () => {
  const el = document.getElementById("goals");
  const data = state.data;

  el.innerHTML = `
    <div class="split">
      <div class="card">
        <h3>Nova meta</h3>
        <form id="goal-form" class="form">
          <label>
            Objetivo
            <input type="text" name="title" required />
          </label>
          <label>
            Descrição
            <textarea name="description"></textarea>
          </label>
          <label>
            Prazo
            <input type="date" name="deadline" />
          </label>
          <button class="btn" type="submit">Adicionar meta</button>
        </form>
      </div>
      <div class="card">
        <h3>Metas atuais</h3>
        <div class="list">
          ${
            data.goals.length
              ? data.goals
                  .map(
                    (goal) => `
            <div class="list-item">
              <div class="list-row">
                <strong>${goal.title}</strong>
                <span class="tag">${goal.completed ? "Concluída" : "Em andamento"}</span>
              </div>
              <p class="muted">${goal.description || "Sem descrição"}</p>
              <div class="list-row">
                <span class="muted">Prazo: ${goal.deadline || "Sem prazo"}</span>
                <div>
                  <button class="btn ghost" data-action="complete-goal" data-id="${goal.id}" ${
                    goal.completed ? "disabled" : ""
                  }>Concluir +${POINTS.goal}</button>
                  <button class="btn ghost" data-action="delete-goal" data-id="${goal.id}">Excluir</button>
                </div>
              </div>
            </div>
          `
                  )
                  .join("")
              : `<p class="muted">Nenhuma meta cadastrada.</p>`
          }
        </div>
      </div>
    </div>
  `;

  document.getElementById("goal-form").addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
  data.goals.push({
      id: uid(),
      title: formData.get("title"),
      description: formData.get("description"),
      deadline: formData.get("deadline"),
      completed: false,
    });
  saveData();
    renderGoals();
    renderDashboard();
  });

  wireActions(el);
};

const renderHabits = () => {
  const el = document.getElementById("habits");
  const data = state.data;

  el.innerHTML = `
    <div class="split">
      <div class="card">
        <h3>Novo hábito</h3>
        <form id="habit-form" class="form">
          <label>
            Hábito
            <input type="text" name="title" required />
          </label>
          <label>
            Motivação
            <input type="text" name="motivation" />
          </label>
          <button class="btn" type="submit">Adicionar hábito</button>
        </form>
      </div>
      <div class="card">
        <h3>Habit tracker</h3>
        <div class="list">
          ${
            data.habits.length
              ? data.habits
                  .map((habit) => {
                    const doneToday = habit.logs?.includes(todayKey());
                    return `
              <div class="list-item">
                <div class="list-row">
                  <strong>${habit.title}</strong>
                  <span class="tag">${doneToday ? "Feito hoje" : "Pendente"}</span>
                </div>
                <p class="muted">${habit.motivation || "Sem motivação"}</p>
                <div class="list-row">
                  <span class="muted">${habit.logs?.length || 0} dias cumpridos</span>
                  <div>
                    <button class="btn ghost" data-action="complete-habit" data-id="${habit.id}" ${
                      doneToday ? "disabled" : ""
                    }>Marcar hoje +${POINTS.habit}</button>
                    <button class="btn ghost" data-action="delete-habit" data-id="${habit.id}">Excluir</button>
                  </div>
                </div>
              </div>
            `;
                  })
                  .join("")
              : `<p class="muted">Nenhum hábito cadastrado.</p>`
          }
        </div>
      </div>
    </div>
  `;

  document.getElementById("habit-form").addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
  data.habits.push({
      id: uid(),
      title: formData.get("title"),
      motivation: formData.get("motivation"),
      logs: [],
    });
  saveData();
    renderHabits();
    renderDashboard();
  });

  wireActions(el);
};

const renderWorkouts = () => {
  const el = document.getElementById("workouts");
  const data = state.data;

  el.innerHTML = `
    <div class="split">
      <div class="card">
        <h3>Novo treino</h3>
        <form id="workout-form" class="form">
          <label>
            Treino
            <input type="text" name="title" required />
          </label>
          <label>
            Duração (min)
            <input type="number" name="duration" min="10" value="30" />
          </label>
          <label>
            Intensidade
            <select name="intensity">
              <option value="Leve">Leve</option>
              <option value="Moderado">Moderado</option>
              <option value="Intenso">Intenso</option>
            </select>
          </label>
          <button class="btn" type="submit">Adicionar treino</button>
        </form>
      </div>
      <div class="card">
        <h3>Treinos</h3>
        <div class="list">
          ${
            data.workouts.length
              ? data.workouts
                  .map(
                    (workout) => `
            <div class="list-item">
              <div class="list-row">
                <strong>${workout.title}</strong>
                <span class="tag">${workout.intensity}</span>
              </div>
              <p class="muted">Duração: ${workout.duration} min</p>
              <div class="list-row">
                <span class="muted">${workout.completed ? "Concluído" : "Pendente"}</span>
                <div>
                  <button class="btn ghost" data-action="complete-workout" data-id="${workout.id}" ${
                    workout.completed ? "disabled" : ""
                  }>Concluir +${POINTS.workout}</button>
                  <button class="btn ghost" data-action="delete-workout" data-id="${workout.id}">Excluir</button>
                </div>
              </div>
            </div>
          `
                  )
                  .join("")
              : `<p class="muted">Nenhum treino cadastrado.</p>`
          }
        </div>
      </div>
    </div>
  `;

  document.getElementById("workout-form").addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
  data.workouts.push({
      id: uid(),
      title: formData.get("title"),
      duration: formData.get("duration"),
      intensity: formData.get("intensity"),
      completed: false,
    });
  saveData();
    renderWorkouts();
    renderDashboard();
  });

  wireActions(el);
};

const renderCategories = () => {
  const el = document.getElementById("categories");
  const data = state.data;

  el.innerHTML = `
    <div class="split">
      <div class="card">
        <h3>Nova categoria</h3>
        <form id="category-form" class="form">
          <label>
            Nome
            <input type="text" name="name" required />
          </label>
          <button class="btn" type="submit">Adicionar categoria</button>
        </form>
      </div>
      <div class="card">
        <h3>Categorias</h3>
        <div class="list">
          ${
            data.categories.length
              ? data.categories
                  .map(
                    (category) => `
            <div class="list-item">
              <div class="list-row">
                <strong>${category.name}</strong>
                <button class="btn ghost" data-action="delete-category" data-id="${category.id}">Excluir</button>
              </div>
            </div>
          `
                  )
                  .join("")
              : `<p class="muted">Nenhuma categoria cadastrada.</p>`
          }
        </div>
      </div>
    </div>
  `;

  document.getElementById("category-form").addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
  data.categories.push({ id: uid(), name: formData.get("name") });
  saveData();
    renderCategories();
    renderTasks();
  });

  wireActions(el);
};

const renderHistory = () => {
  const el = document.getElementById("history");
  const data = state.data;

  el.innerHTML = `
    <div class="card">
      <h3>Histórico de conquistas</h3>
      <div class="list">
        ${
          data.logs.length
            ? data.logs
                .map(
                  (log) => `
          <div class="list-item">
            <div class="list-row">
              <strong>${log.title}</strong>
              <span class="tag">+${log.points}</span>
            </div>
            <p class="muted">${log.type} · ${formatDate(log.date)}</p>
          </div>
        `
                )
                .join("")
            : `<p class="muted">Sem registros ainda.</p>`
        }
      </div>
    </div>
  `;
};

const renderMissions = () => {
  const el = document.getElementById("missions");
  const data = state.data;
  if (!el) return;

  el.innerHTML = `
    <div class="card">
      <h3>Missões diárias</h3>
      <p class="muted">Complete para ganhar pontos extras.</p>
      <div class="list">
        ${
          data.missions?.length
            ? data.missions
                .map(
                  (mission) => `
            <div class="list-item">
              <div class="list-row">
                <strong>${mission.title}</strong>
                <span class="tag">+${mission.points}</span>
              </div>
              <p class="muted">${mission.description || "Missão diária"}</p>
              <div class="list-row">
                <span class="muted">${mission.completed ? "Concluída" : "Pendente"}</span>
                <button class="btn ghost" data-action="complete-mission" data-id="${mission.id}" ${
                  mission.completed ? "disabled" : ""
                }>Concluir</button>
              </div>
            </div>
          `
                )
                .join("")
            : `<p class="muted">Nenhuma missão disponível hoje.</p>`
        }
      </div>
    </div>
  `;

  wireActions(el);
};

const renderAchievements = () => {
  const el = document.getElementById("achievements");
  const data = state.data;
  if (!el) return;

  el.innerHTML = `
    <div class="card">
      <h3>Conquistas & Badges</h3>
      <p class="muted">Desbloqueie conquistas para ganhar pontos.</p>
      <div class="list">
        ${
          data.achievements?.length
            ? data.achievements
                .map(
                  (achievement) => `
            <div class="list-item">
              <div class="list-row">
                <strong>${achievement.title}</strong>
                <span class="tag">+${achievement.points}</span>
              </div>
              <p class="muted">${achievement.description || "Conquista"}</p>
              <div class="list-row">
                <span class="muted">${achievement.unlocked ? "Desbloqueada" : "Bloqueada"}</span>
                <button class="btn ghost" data-action="unlock-achievement" data-id="${achievement.id}" ${
                  achievement.unlocked ? "disabled" : ""
                }>Desbloquear</button>
              </div>
            </div>
          `
                )
                .join("")
            : `<p class="muted">Nenhuma conquista cadastrada.</p>`
        }
      </div>
    </div>
  `;

  wireActions(el);
};

const renderRanking = () => {
  const el = document.getElementById("ranking");
  const data = state.data;
  if (!el) return;

  el.innerHTML = `
    <div class="card">
      <h3>Ranking global</h3>
      <div class="list">
        ${
          data.ranking?.length
            ? data.ranking
                .map(
                  (player, index) => `
            <div class="list-item">
              <div class="list-row">
                <strong>#${index + 1} ${player.name}</strong>
                <span class="tag">${player.points} pts</span>
              </div>
            </div>
          `
                )
                .join("")
            : `<p class="muted">Ranking vazio.</p>`
        }
      </div>
    </div>
  `;
};

const renderProfile = () => {
  const el = document.getElementById("profile");
  const data = state.data;
  if (!el) return;

  el.innerHTML = `
    <div class="split">
      <div class="card">
        <h3>Perfil</h3>
        <form id="profile-form" class="form">
          <label>
            Nome exibido
            <input type="text" name="displayName" value="${data.profile?.displayName || ""}" />
          </label>
          <label>
            URL do avatar
            <input type="text" name="avatarUrl" value="${data.profile?.avatarUrl || ""}" />
          </label>
          <label>
            Bio
            <textarea name="bio">${data.profile?.bio || ""}</textarea>
          </label>
          <label>
            Foco principal
            <input type="text" name="focusArea" value="${data.profile?.focusArea || ""}" />
          </label>
          <button class="btn" type="submit">Salvar perfil</button>
        </form>
      </div>
      <div class="card">
        <h3>Preview</h3>
        <div class="list-item">
          <div class="list-row">
            <strong>${data.profile?.displayName || state.user?.name || "Usuário"}</strong>
            <span class="tag">${data.points} pts</span>
          </div>
          <p class="muted">${data.profile?.bio || "Sem bio ainda."}</p>
          <p class="muted">Foco: ${data.profile?.focusArea || "Defina seu foco"}</p>
        </div>
      </div>
    </div>
  `;

  const form = document.getElementById("profile-form");
  form.addEventListener("submit", (event) => {
    event.preventDefault();
    const formData = new FormData(form);
    data.profile = {
      displayName: formData.get("displayName"),
      avatarUrl: formData.get("avatarUrl"),
      bio: formData.get("bio"),
      focusArea: formData.get("focusArea"),
    };
    saveData();
    renderProfile();
  });
};

const renderAdmin = () => {
  const el = document.getElementById("admin");
  if (!el) return;

  if (state.user?.role !== "admin") {
    el.innerHTML = `
      <div class="card">
        <h3>Admin</h3>
        <p class="muted">Acesso restrito.</p>
      </div>
    `;
    return;
  }

  el.innerHTML = `
    <div class="split">
      <div class="card">
        <h3>Missão diária</h3>
        <form id="admin-mission-form" class="form">
          <label>Título <input type="text" name="title" required /></label>
          <label>Descrição <input type="text" name="description" /></label>
          <label>Pontos <input type="number" name="points" min="0" value="20" /></label>
          <label>Data <input type="date" name="date" value="${new Date().toISOString().slice(0, 10)}" /></label>
          <button class="btn" type="submit">Salvar missão</button>
        </form>
      </div>
      <div class="card">
        <h3>Conquista</h3>
        <form id="admin-achievement-form" class="form">
          <label>Título <input type="text" name="title" required /></label>
          <label>Descrição <input type="text" name="description" /></label>
          <label>Pontos <input type="number" name="points" min="0" value="50" /></label>
          <button class="btn" type="submit">Salvar conquista</button>
        </form>
      </div>
    </div>
    <div class="card" style="margin-top:18px;">
      <h3>Resumo</h3>
      <p class="muted">Usuários totais: ${state.admin?.totalUsers ?? 0}</p>
    </div>
  `;

  const missionForm = document.getElementById("admin-mission-form");
  missionForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(missionForm);
    await apiRequest("admin_save", {
      mission: {
        title: formData.get("title"),
        description: formData.get("description"),
        points: formData.get("points"),
        date: formData.get("date"),
      },
    });
    openApp();
  });

  const achievementForm = document.getElementById("admin-achievement-form");
  achievementForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const formData = new FormData(achievementForm);
    await apiRequest("admin_save", {
      achievement: {
        title: formData.get("title"),
        description: formData.get("description"),
        points: formData.get("points"),
      },
    });
    openApp();
  });
};

const renderAll = () => {
  renderHeader();
  renderDashboard();
  renderTasks();
  renderGoals();
  renderHabits();
  renderWorkouts();
  renderCategories();
  renderMissions();
  renderAchievements();
  renderRanking();
  renderProfile();
  renderAdmin();
  renderHistory();
};

const getCategoryName = (id) => {
  const category = state.data.categories.find((cat) => cat.id === id);
  return category ? category.name : "Sem categoria";
};

const wireActions = (container) => {
  container.querySelectorAll("[data-action]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const { action, id } = btn.dataset;
      if (!action || !id) return;
  const data = state.data;

      if (action === "complete-task") {
        const task = data.tasks.find((item) => item.id === id);
        if (task && !task.completed) {
          task.completed = true;
          addPoints(data, POINTS.task, `Tarefa concluída: ${task.title}`, "Agenda");
        }
      }

      if (action === "delete-task") {
        data.tasks = data.tasks.filter((item) => item.id !== id);
      }

      if (action === "complete-goal") {
        const goal = data.goals.find((item) => item.id === id);
        if (goal && !goal.completed) {
          goal.completed = true;
          addPoints(data, POINTS.goal, `Meta concluída: ${goal.title}`, "Metas");
        }
      }

      if (action === "delete-goal") {
        data.goals = data.goals.filter((item) => item.id !== id);
      }

      if (action === "complete-habit") {
        const habit = data.habits.find((item) => item.id === id);
        if (habit) {
          habit.logs = habit.logs || [];
          const today = todayKey();
          if (!habit.logs.includes(today)) {
            habit.logs.push(today);
            addPoints(data, POINTS.habit, `Hábito cumprido: ${habit.title}`, "Hábitos");
          }
        }
      }

      if (action === "delete-habit") {
        data.habits = data.habits.filter((item) => item.id !== id);
      }

      if (action === "complete-workout") {
        const workout = data.workouts.find((item) => item.id === id);
        if (workout && !workout.completed) {
          workout.completed = true;
          addPoints(data, POINTS.workout, `Treino concluído: ${workout.title}`, "Treinos");
        }
      }

      if (action === "delete-workout") {
        data.workouts = data.workouts.filter((item) => item.id !== id);
      }

      if (action === "delete-category") {
        if (data.categories.length <= 1) {
          alert("Mantenha ao menos uma categoria.");
          return;
        }
        data.categories = data.categories.filter((item) => item.id !== id);
      }

      if (action === "complete-mission") {
        const mission = data.missions?.find((item) => item.id === id);
        if (mission && !mission.completed) {
          mission.completed = true;
          addPoints(data, mission.points || 0, `Missão concluída: ${mission.title}`, "Missões");
        }
      }

      if (action === "unlock-achievement") {
        const achievement = data.achievements?.find((item) => item.id === id);
        if (achievement && !achievement.unlocked) {
          achievement.unlocked = true;
          addPoints(data, achievement.points || 0, `Conquista desbloqueada: ${achievement.title}`, "Conquistas");
        }
      }

      saveData();
      renderAll();
    });
  });
};

const initNavigation = () => {
  const sidebar = document.querySelector(".sidebar");
  if (!sidebar || sidebar.dataset.bound === "true") return;
  sidebar.dataset.bound = "true";
  sidebar.addEventListener("click", (event) => {
    const btn = event.target.closest(".nav-link");
    if (!btn) return;
    showView(btn.dataset.view);
  });
};

const openApp = async () => {
  if (!appSection) return;
  initNavigation();
  showView("dashboard");
  const response = await fetchData();
  if (!response.ok) {
    window.location.href = "index.php";
    return;
  }
  state.user = response.user;
  state.data = response.data;
  state.admin = response.admin || null;
  const adminBtn = document.querySelector('.nav-link[data-view="admin"]');
  if (adminBtn && state.user?.role !== "admin") {
    adminBtn.classList.add("hidden");
  }
  renderAll();
};

const saveData = async () => {
  if (!state.data) return;
  await apiRequest("save", { data: state.data });
};

if (loginForm) {
  loginForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    resetMessages();
    const formData = new FormData(event.target);
    const response = await fetch("auth.php", {
      method: "POST",
      body: formData,
    });
    let result;
    try {
      result = await response.json();
    } catch (err) {
      showMessage("login", "Erro de comunicação com o servidor.");
      return;
    }
    if (!result.ok) {
      showMessage("login", result.message || "Erro ao entrar.");
      return;
    }
    window.location.href = "app.php";
  });
}

if (registerForm) {
  registerForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    resetMessages();
    const formData = new FormData(event.target);
    const response = await fetch("auth.php", {
      method: "POST",
      body: formData,
    });
    let result;
    try {
      result = await response.json();
    } catch (err) {
      showMessage("register", "Erro de comunicação com o servidor.");
      return;
    }
    if (!result.ok) {
      showMessage("register", result.message || "Erro ao cadastrar.");
      return;
    }
    // redirect to login page so user can authenticate
    window.location.href = "index.php?registered=1";
  });
}

// forgot password form
const forgotForm = document.getElementById("forgot-form");
if (forgotForm) {
  forgotForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    resetMessages();
    const formData = new FormData(event.target);
    const response = await fetch("auth.php", { method: "POST", body: formData });
    let result;
    try {
      result = await response.json();
    } catch (err) {
      showMessage("forgot", "Erro de comunicação com o servidor.");
      return;
    }
    showMessage("forgot", result.message || "Verifique seu email.");
    if (result.link && !result.sent) {
      // show link for manual copy (mail likely not configured)
      const el = document.querySelector(`[data-message="forgot"]`);
      if (el) el.innerHTML += `<br><a href="${result.link}">Abrir link de recuperação</a>`;
    }
  });
}

// reset form
const resetForm = document.getElementById("reset-form");
if (resetForm) {
  resetForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    resetMessages();
    const formData = new FormData(event.target);
    const response = await fetch("auth.php", { method: "POST", body: formData });
    let result;
    try {
      result = await response.json();
    } catch (err) {
      showMessage("reset", "Erro de comunicação com o servidor.");
      return;
    }
    if (!result.ok) {
      showMessage("reset", result.message || "Erro ao redefinir senha.");
      return;
    }
    // success -> redirect to login with message
    window.location.href = "index.php?reset=1";
  });
}

const logoutBtn = document.getElementById("logout");
if (logoutBtn) {
  logoutBtn.addEventListener("click", () => {
    window.location.href = "logout.php";
  });
}

openApp();
