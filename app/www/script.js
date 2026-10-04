// Shared JavaScript for database-interaction.html: generic data-script buttons
// plus the roles and users CRUD tables.

// The <pre> box where every server reply is shown.
const result = document.getElementById("result");

// Replaces the 5 characters that mean something in HTML (& < > " ') with safe
// codes. Needed wherever data goes into innerHTML, so a name like
// "<script>..." shows as text instead of running.
function escapeHtml(value) {
  return String(value ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#39;");
}

// runs wghen clicking buttons with data-script attribute
document.querySelectorAll("button[data-script]").forEach((button) => {
  button.addEventListener("click", async () => {
    console.log(`Running script: ${button.dataset.script}`);
    // dataset.script reads the data-script="..." attribute.
    const name = button.dataset.script;
    result.textContent = `Running ${name}...`;
    try {
      // await pauses until the request finishes (needs an async function).
      const res = await fetch(`/api/run/${name}`);

      const data = await res.json();

      // null, 2 = pretty-print the JSON with 2-space indentation.
      result.textContent = JSON.stringify(data, null, 2);
    } catch (err) {
      result.textContent = `Request failed: ${err}`;
    }
  });
});

// Calls one API function and returns the parsed JSON.
// With a body: POST it as JSON. Without: a plain GET.
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

// Grab the page elements once and reuse them.

const rolesTableBody = document.querySelector("#roles-table tbody");
const addRoleForm = document.getElementById("add-role-form");
const cancelRoleEdit = document.getElementById("cancel-role-edit");

const userRoleSelect = document.getElementById("user-role-select");

// Rebuilds the role dropdown on the user form, keeping the current selection.
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

// Fetches every role and redraws the roles table.
async function loadRoles() {
  const data = await callScript("role_crud/list_roles");
  if (data.status !== "ok") return;

  // Clear the table, then add one row per role.
  rolesTableBody.innerHTML = "";
  for (const role of data.roles) {
    const row = document.createElement("tr");
    // innerHTML parses the string as HTML, so every value goes through escapeHtml.
    // data-id / data-name store the role on the button for the click handler below.
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

// Puts the role form back into "Add" mode.
function resetRoleForm() {
  addRoleForm.reset();
  addRoleForm.removeAttribute("data-editing-id");
  addRoleForm.id.disabled = false;
  addRoleForm.querySelector("button[type=submit]").textContent = "Add Role";
  cancelRoleEdit.hidden = true;
}

// One click listener on the whole table instead of one per button ("event delegation").
// It still works for rows added later.
rolesTableBody.addEventListener("click", async (event) => {
  // closest() finds the button that was clicked (or contains what was clicked).
  const button = event.target.closest("button[data-action]");
  if (!button) return;
  const id = button.dataset.id;

  if (button.dataset.action === "delete-role") {
    result.textContent = "Deleting role...";
    const data = await callScript("role_crud/delete_role", { id });
    result.textContent = JSON.stringify(data, null, 2);
    if (data.status === "ok") loadRoles();
  }

  // Edit: copy the role into the form and switch it to "Update" mode.
  // The id box is disabled because the id can't be changed.
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

// Add or update, depending on whether the form has a data-editing-id.
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
    // Ternary: condition ? if-true : if-false
    const script = editingId ? "role_crud/update_role" : "role_crud/add_role";
    const data = await callScript(script, body);
    result.textContent = JSON.stringify(data, null, 2);
    if (data.status === "ok") {
      // Success: clear the form and reload the table to show the change.
      resetRoleForm();
      loadRoles();
    }
  } catch (err) {
    result.textContent = `Request failed: ${err}`;
  }
});

// --- Users ---
// Same pattern as the roles section above.

const usersTableBody = document.querySelector("#users-table tbody");
const addUserForm = document.getElementById("add-user-form");
const cancelUserEdit = document.getElementById("cancel-user-edit");

// Fetches every user (with role name) and redraws the users table.
async function loadUsers() {
  const data = await callScript("user_crud/list_users");
  if (data.status !== "ok") return;

  usersTableBody.innerHTML = "";
  for (const user of data.users) {
    const row = document.createElement("tr");
    // user.roleName ?? "" shows an empty cell when the user has no role (null).
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
    // "" (no role chosen) becomes null.
    roleId: form.roleId.value || null,
  };
  // Only an update sends the id; a new user gets its id from the database.
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

// Fill both tables when the page first loads.
loadRoles();
loadUsers();
