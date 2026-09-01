const result = document.getElementById("result");

function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

document.querySelectorAll("button[data-script]").forEach((button) => {
  button.addEventListener("click", async () => {
    const name = button.dataset.script;
    result.textContent = `Running ${name}...`;
    try {
      const res = await fetch(`/api/run/${name}`);

      const data = await res.json();

      result.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      result.textContent = `Request failed: ${err}`;
    }
  });
});

async function callScript(name, body) {
  const options = body
    ? {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body),
      }
    : {};
  const res = await fetch(`/api/run/${name}`, options);
  return res.json();
}

// --- Roles ---

const rolesTableBody = document.querySelector("#roles-table tbody");
const addRoleForm = document.getElementById("add-role-form");
const cancelRoleEdit = document.getElementById("cancel-role-edit");

const userRoleSelect = document.getElementById("user-role-select");

function populateRoleOptions(roles) {
  const previousValue = userRoleSelect.value;
  userRoleSelect.innerHTML = '<option value="">-- No role --</option>';
  for (const role of roles) {
    const option = document.createElement("option");
    option.value = role.id;
    option.textContent = role.name;
    userRoleSelect.appendChild(option);
  }
  userRoleSelect.value = previousValue;
}

async function loadRoles() {
  const data = await callScript("role_crud/list_roles");
  if (data.status !== "ok") return;

  rolesTableBody.innerHTML = "";
  for (const role of data.roles) {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${escapeHtml(role.id)}</td>
      <td>${escapeHtml(role.name)}</td>
      <td>
        <button type="button" data-action="edit-role" data-id="${escapeHtml(role.id)}" data-name="${escapeHtml(role.name)}">Edit</button>
        <button type="button" data-action="delete-role" data-id="${escapeHtml(role.id)}">Delete</button>
      </td>
    `;
    rolesTableBody.appendChild(row);
  }
  populateRoleOptions(data.roles);
}

function resetRoleForm() {
  addRoleForm.reset();
  addRoleForm.removeAttribute("data-editing-id");
  addRoleForm.id.disabled = false;
  addRoleForm.querySelector("button[type=submit]").textContent = "Add Role";
  cancelRoleEdit.hidden = true;
}

rolesTableBody.addEventListener("click", async (event) => {
  const button = event.target.closest("button[data-action]");
  if (!button) return;
  const id = button.dataset.id;

  if (button.dataset.action === "delete-role") {
    result.textContent = "Deleting role...";
    const data = await callScript("role_crud/delete_role", { id });
    result.textContent = JSON.stringify(data, null, 2);
    if (data.status === "ok") loadRoles();
  }

  if (button.dataset.action === "edit-role") {
    addRoleForm.dataset.editingId = id;
    addRoleForm.id.value = id;
    addRoleForm.id.disabled = true;
    addRoleForm.name.value = button.dataset.name;
    addRoleForm.querySelector("button[type=submit]").textContent =
      "Update Role";
    cancelRoleEdit.hidden = false;
  }
});

cancelRoleEdit.addEventListener("click", resetRoleForm);

addRoleForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.target;
  const editingId = form.dataset.editingId;
  const body = {
    id: editingId || form.id.value,
    name: form.name.value,
  };

  result.textContent = editingId ? "Updating role..." : "Adding role...";
  try {
    const script = editingId ? "role_crud/update_role" : "role_crud/add_role";
    const data = await callScript(script, body);
    result.textContent = JSON.stringify(data, null, 2);
    if (data.status === "ok") {
      resetRoleForm();
      loadRoles();
    }
  } catch (err) {
    result.textContent = `Request failed: ${err}`;
  }
});

// --- Users ---

const usersTableBody = document.querySelector("#users-table tbody");
const addUserForm = document.getElementById("add-user-form");
const cancelUserEdit = document.getElementById("cancel-user-edit");

async function loadUsers() {
  const data = await callScript("user_crud/list_users");
  if (data.status !== "ok") return;

  usersTableBody.innerHTML = "";
  for (const user of data.users) {
    const row = document.createElement("tr");
    row.innerHTML = `
      <td>${escapeHtml(user.id)}</td>
      <td>${escapeHtml(user.firstName)}</td>
      <td>${escapeHtml(user.lastName)}</td>
      <td>${escapeHtml(user.email)}</td>
      <td>${escapeHtml(user.roleName ?? "")}</td>
      <td>
        <button type="button" data-action="edit-user" data-id="${escapeHtml(user.id)}" data-first-name="${escapeHtml(user.firstName)}" data-last-name="${escapeHtml(user.lastName)}" data-email="${escapeHtml(user.email)}" data-role-id="${escapeHtml(user.roleId ?? "")}">Edit</button>
        <button type="button" data-action="delete-user" data-id="${escapeHtml(user.id)}">Delete</button>
      </td>
    `;
    usersTableBody.appendChild(row);
  }
}

function resetUserForm() {
  addUserForm.reset();
  addUserForm.removeAttribute("data-editing-id");
  addUserForm.querySelector("button[type=submit]").textContent = "Add User";
  cancelUserEdit.hidden = true;
}

usersTableBody.addEventListener("click", async (event) => {
  const button = event.target.closest("button[data-action]");
  if (!button) return;
  const id = button.dataset.id;

  if (button.dataset.action === "delete-user") {
    result.textContent = "Deleting user...";
    const data = await callScript("user_crud/delete_user", { id });
    result.textContent = JSON.stringify(data, null, 2);
    if (data.status === "ok") loadUsers();
  }

  if (button.dataset.action === "edit-user") {
    addUserForm.dataset.editingId = id;
    addUserForm.firstName.value = button.dataset.firstName;
    addUserForm.lastName.value = button.dataset.lastName;
    addUserForm.email.value = button.dataset.email;
    addUserForm.roleId.value = button.dataset.roleId;
    addUserForm.querySelector("button[type=submit]").textContent =
      "Update User";
    cancelUserEdit.hidden = false;
  }
});

cancelUserEdit.addEventListener("click", resetUserForm);

addUserForm.addEventListener("submit", async (event) => {
  event.preventDefault();
  const form = event.target;
  const editingId = form.dataset.editingId;
  const body = {
    firstName: form.firstName.value,
    lastName: form.lastName.value,
    email: form.email.value,
    roleId: form.roleId.value || null,
  };
  if (editingId) body.id = editingId;

  result.textContent = editingId ? "Updating user..." : "Adding user...";
  try {
    const script = editingId ? "user_crud/update_user" : "user_crud/add_user";
    const data = await callScript(script, body);
    result.textContent = JSON.stringify(data, null, 2);
    if (data.status === "ok") {
      resetUserForm();
      loadUsers();
    }
  } catch (err) {
    result.textContent = `Request failed: ${err}`;
  }
});

loadRoles();
loadUsers();
