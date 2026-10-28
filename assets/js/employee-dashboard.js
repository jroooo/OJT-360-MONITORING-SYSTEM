function initials(name){

    if(!name) return "??";

    return name

        .split(" ")

        .map(part => part[0])

        .join("")

        .slice(0,2)

        .toUpperCase();

}

 

function escapeHtml(value){

    return String(value ?? "")

        .replaceAll("&","&amp;")

        .replaceAll("<","&lt;")

        .replaceAll(">","&gt;")

        .replaceAll('"',"&quot;")

        .replaceAll("'","&#039;");

}

 

/* ================================================================

   SIDEBAR / PAGE NAVIGATION

================================================================ */

 

const navLinks = document.querySelectorAll(".nav-link[data-page]");

const pages = document.querySelectorAll(".page");

const sidebar = document.getElementById("sidebar");

const sidebarOverlay = document.getElementById("sidebarOverlay");

const menuToggle = document.getElementById("menuToggle");

const themeToggle = document.getElementById("themeToggle");
const settingsSidebarToggle = document.getElementById("settingsSidebarToggle");
const settingsMotionToggle = document.getElementById("settingsMotionToggle");

const topbarPageName = document.getElementById("topbarPageName");

const themeStorageKey = "ojt360-employee-theme-" + CURRENT_EMPLOYEE_ID;

let keepSidebarOpen = false;

 

function setDashboardTheme(theme){

    const isDark = theme === "dark" ||

        (theme === "system" && window.matchMedia("(prefers-color-scheme: dark)").matches);

    document.body.dataset.theme = isDark ? "dark" : "light";

    themeToggle.setAttribute("aria-label", isDark ? "Switch to light mode" : "Switch to dark mode");

    themeToggle.title = isDark ? "Switch to light mode" : "Switch to dark mode";

 

    document.querySelectorAll("[data-settings-theme]").forEach(button => {

        const selected = button.dataset.settingsTheme === theme;

        button.classList.toggle("selected", selected);

        button.setAttribute("aria-pressed", String(selected));

    });

}

 

let savedTheme = "light";

try{

    savedTheme = localStorage.getItem(themeStorageKey) ||

        localStorage.getItem("ojt360-employee-theme") ||

        "light";

    if(!["light", "dark", "system"].includes(savedTheme)) savedTheme = "light";

}catch(error){

    console.error("Unable to load saved theme.", error);

}

setDashboardTheme(savedTheme);

// Keep the top-bar theme button and Settings theme cards working together.
themeToggle?.addEventListener("click", () => {
    const current = document.body.dataset.theme === "dark" ? "dark" : "light";
    const next = current === "dark" ? "light" : "dark";
    try {
        localStorage.setItem(themeStorageKey, next);
        localStorage.setItem("ojt360-employee-theme", next);
    } catch (error) {
        console.error("Unable to save theme preference.", error);
    }
    setDashboardTheme(next);
});

document.querySelectorAll("[data-settings-theme]").forEach(button => {
    button.addEventListener("click", () => {
        const theme = button.dataset.settingsTheme;
        if (!["light", "dark", "system"].includes(theme)) return;
        try {
            localStorage.setItem(themeStorageKey, theme);
            localStorage.setItem("ojt360-employee-theme", theme);
        } catch (error) {
            console.error("Unable to save theme preference.", error);
        }
        setDashboardTheme(theme);
    });
});

// Profile shortcuts open the dedicated adviser profile page.
function openEmployeeProfile() {
    window.location.href = "employee_profile.php";
}

document.getElementById("sidebarProfile")?.addEventListener("click", openEmployeeProfile);
document.getElementById("sidebarProfile")?.addEventListener("keydown", event => {
    if (event.key === "Enter" || event.key === " ") {
        event.preventDefault();
        openEmployeeProfile();
    }
});
document.getElementById("topProfileBtn")?.addEventListener("click", openEmployeeProfile);

// Use the existing logout endpoint so the session is cleared securely.
document.getElementById("signOutBtn")?.addEventListener("click", () => {
    window.location.href = "logout.php";
});

// Persist the sidebar and reduced-motion preferences on this browser.
if (settingsSidebarToggle) {
    try {
        settingsSidebarToggle.checked = localStorage.getItem("ojt360-sidebar-open") === "true";
    } catch (error) {}
    settingsSidebarToggle.addEventListener("change", () => {
        try { localStorage.setItem("ojt360-sidebar-open", String(settingsSidebarToggle.checked)); } catch (error) {}
        sidebar?.classList.toggle("compact", !settingsSidebarToggle.checked);
    });
}
if (settingsMotionToggle) {
    try {
        settingsMotionToggle.checked = localStorage.getItem("ojt360-reduced-motion") === "true";
        document.documentElement.classList.toggle("reduce-motion", settingsMotionToggle.checked);
    } catch (error) {}
    settingsMotionToggle.addEventListener("change", () => {
        try { localStorage.setItem("ojt360-reduced-motion", String(settingsMotionToggle.checked)); } catch (error) {}
        document.documentElement.classList.toggle("reduce-motion", settingsMotionToggle.checked);
    });
}

 

function closeSidebar(){

    if(window.innerWidth <= 1000){

        sidebar.classList.remove("open");

    }

    sidebarOverlay.classList.remove("show");

    menuToggle.setAttribute("aria-expanded", "false");

}

 

function openSidebar(){

    if(window.innerWidth > 1000){

        sidebar.classList.remove("compact");

        keepSidebarOpen = true;

        settingsSidebarToggle.checked = true;

    } else {

        sidebar.classList.add("open");

        sidebarOverlay.classList.add("show");

        menuToggle.setAttribute("aria-expanded", "true");

    }

}

 

function showPage(pageName){

    const pageLabels = {

        overview: "Overview",

        interns: "Interns",

        evaluations: "Evaluations",

        messages: "Messages",

        companies: "Companies",

        support: "Help & Support",

        settings: "Settings",
        profile: "Profile"

    };

 

    pages.forEach(page => {

        page.classList.toggle("active", page.id === "page-" + pageName);

    });

 

    navLinks.forEach(link => {

        link.classList.toggle("active", link.dataset.page === pageName);

    });

 

    topbarPageName.dataset.currentPage = pageLabels[pageName] || "Employee";

    closeSidebar();

    window.scrollTo({top:0,behavior:"smooth"});

}

 

const requestedSection = window.OJT360_INITIAL_SECTION || new URLSearchParams(window.location.search).get("section");

if(["overview", "interns", "evaluations", "messages", "companies", "support", "settings", "profile"].includes(requestedSection)){

    showPage(requestedSection);

}

 

navLinks.forEach(link => {

    link.addEventListener("click", event => {
        // Sidebar items are links to separate PHP entry points. Allow normal navigation.
        if (link.tagName.toLowerCase() !== "a") showPage(link.dataset.page);
    });

});

 

menuToggle.addEventListener("click", () => {

    if(window.innerWidth > 1000){

        keepSidebarOpen = sidebar.classList.contains("compact");

        sidebar.classList.toggle("compact", !keepSidebarOpen);

        return;

    }

 

    if(sidebar.classList.contains("open")) closeSidebar();

    else openSidebar();

});

 

document.getElementById("sidebarClose").addEventListener("click", closeSidebar);

sidebarOverlay.addEventListener("click", closeSidebar);

 

/* ================================================================

   COMPANIES PAGE

================================================================ */

 

function renderCompaniesPage(){

    const container = document.getElementById("companyPlacementContainer");

    const search = (document.getElementById("companyPageSearch")?.value || "").toLowerCase().trim();

 

    if(!companies.length){

        container.innerHTML = `<div class="empty">No companies registered yet.</div>`;

        return;

    }

 

    let html = "";

 

    companies.forEach(company => {

        const companyStudents = students.filter(s => s.company === company.name);

        

        const matchesCompany = company.name.toLowerCase().includes(search) || company.code.toLowerCase().includes(search);

        const matchesStudent = companyStudents.some(s => s.name.toLowerCase().includes(search) || s.studentId.toLowerCase().includes(search));

        

        if(search && !matchesCompany && !matchesStudent) return;

 

        html += `

            <div class="section-card" style="margin-bottom: 20px; border: 1px solid var(--border-color, #e0e8f0);">

                <div class="section-head" style="margin-bottom: 12px;">

                    <div>

                        <div class="person">

                            <div class="mini-avatar" style="width:36px;height:36px;font-size:14px;">${initials(company.name)}</div>

                            <div>

                                <h3 style="margin:0;font-size:16px;">${escapeHtml(company.name)}</h3>

                                <p class="section-sub" style="margin:2px 0 0 0;">Code: ${escapeHtml(company.code || 'N/A')} | Contact: ${escapeHtml(company.contact)} (${escapeHtml(company.email)})</p>

                            </div>

                        </div>

                    </div>

                    <div>

                        <span class="badge ${company.status === 'active' ? 'green' : 'yellow'}">${escapeHtml(company.status.toUpperCase())}</span>

                    </div>

                </div>

 

                <div style="margin-bottom: 10px; padding-left: 8px; font-weight:600; font-size:13px; color:#526475;">

                    Assigned Student Interns (${companyStudents.length})

                </div>

 

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>Student Name</th>

                                <th>Student ID</th>

                                <th>Program</th>

                                <th>Logged Hours</th>

                                <th>Absences</th>

                                <th>Weekly Journals</th>

                                <th>Actions</th>

                            </tr>

                        </thead>

                        <tbody>

                            ${companyStudents.length === 0 ? `

                                <tr>

                                    <td colspan="7"><div class="empty" style="padding:12px;">No students assigned to this company yet.</div></td>

                                </tr>

                            ` : companyStudents.map(student => `

                                <tr>

                                    <td>

                                        <div class="person">

                                            <div class="mini-avatar">${initials(student.name)}</div>

                                            <div class="person-name">${escapeHtml(student.name)}</div>

                                        </div>

                                    </td>

                                    <td>${escapeHtml(student.studentId)}</td>

                                    <td>${escapeHtml(student.program)}</td>

                                    <td><strong>${Number(student.completedHours).toFixed(1)} hrs</strong></td>

                                    <td>

                                        <span class="badge ${student.absences.length > 0 ? 'yellow' : 'green'}">

                                            ${student.absences.length} record(s)

                                        </span>

                                    </td>

                                    <td>

                                        <span class="badge blue">

                                            ${student.journals.length} journal(s)

                                        </span>

                                    </td>

                                    <td>

                                        <div class="action-row">

                                            <button class="small-btn" type="button" onclick="openStudentAdviserRecord(${student.id})">View Record</button>

                                            <button class="small-btn" type="button" onclick="assignCompany(${student.id})">Reassign</button>

                                        </div>

                                    </td>

                                </tr>

                            `).join("")}

                        </tbody>

                    </table>

                </div>

            </div>

        `;

    });

 

    const unassignedStudents = students.filter(s => !s.company);

    if(unassignedStudents.length > 0 && (!search || "unassigned".includes(search))){

        html += `

            <div class="section-card" style="margin-top: 30px; border: 1px dashed #c0d0e0;">

                <div class="section-head">

                    <div>

                        <h3 style="margin:0;font-size:16px;color:#d9534f;">Unassigned Student Interns (${unassignedStudents.length})</h3>

                        <p class="section-sub">Students currently waiting for company assignment.</p>

                    </div>

                </div>

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>Student Name</th>

                                <th>Student ID</th>

                                <th>Program</th>

                                <th>Institutional Email</th>

                                <th>Action</th>

                            </tr>

                        </thead>

                        <tbody>

                            ${unassignedStudents.map(s => `

                                <tr>

                                    <td>

                                        <div class="person">

                                            <div class="mini-avatar">${initials(s.name)}</div>

                                            <div class="person-name">${escapeHtml(s.name)}</div>

                                        </div>

                                    </td>

                                    <td>${escapeHtml(s.studentId)}</td>

                                    <td>${escapeHtml(s.program)}</td>

                                    <td>${escapeHtml(s.email)}</td>

                                    <td>

                                        <button class="small-btn primary" type="button" onclick="assignCompany(${s.id})">Assign to Company</button>

                                    </td>

                                </tr>

                            `).join("")}

                        </tbody>

                    </table>

                </div>

            </div>

        `;

    }

 

    container.innerHTML = html || `<div class="empty">No matching companies or students found.</div>`;

}

 

document.getElementById("companyPageSearch")?.addEventListener("input", renderCompaniesPage);

document.getElementById("addCompanyPageBtn")?.addEventListener("click", () => {

    document.getElementById("companyModal").classList.add("show");

});

 

/* ================================================================

   ADVISER VIEW STUDENT RECORD MODAL

================================================================ */

 

function openStudentAdviserRecord(studentId){

    const student = students.find(s => s.id === studentId);

    if(!student) return;

 

    document.getElementById("advStudentName").textContent = student.name;

    document.getElementById("advStudentId").textContent = student.studentId;

    document.getElementById("advCompany").textContent = student.company || "Unassigned";

    document.getElementById("advCompletedHours").textContent = Number(student.completedHours).toFixed(1) + " hrs";

 

    const absTbody = document.getElementById("advAbsenceTable");

    if(!student.absences || student.absences.length === 0){

        absTbody.innerHTML = `<tr><td colspan="3"><div class="empty" style="padding:10px;">No absence records logged.</div></td></tr>`;

    } else {

        absTbody.innerHTML = student.absences.map(abs => `

            <tr>

                <td>${escapeHtml(abs.absence_date)}</td>

                <td>${escapeHtml(abs.reason || 'No reason provided')}</td>

                <td><span class="badge ${abs.status === 'Excused' ? 'green' : 'yellow'}">${escapeHtml(abs.status || 'Unexcused')}</span></td>

            </tr>

        `).join("");

    }

 

    const jnlTbody = document.getElementById("advJournalTable");

    if(!student.journals || student.journals.length === 0){

        jnlTbody.innerHTML = `<tr><td colspan="4"><div class="empty" style="padding:10px;">No weekly journals submitted yet.</div></td></tr>`;

    } else {

        jnlTbody.innerHTML = student.journals.map(j => `

            <tr>

                <td><strong>Week ${escapeHtml(j.week_number)}</strong><br><small>${escapeHtml(j.start_date)} to ${escapeHtml(j.end_date)}</small></td>

                <td>${escapeHtml(j.journal_entry)}</td>

                <td><span class="badge ${j.status === 'Approved' ? 'green' : 'yellow'}">${escapeHtml(j.status || 'Pending')}</span></td>

                <td>${escapeHtml(j.feedback || 'None')}</td>

            </tr>

        `).join("");

    }

 

    document.getElementById("studentAdviserRecordModal").classList.add("show");

}

 

/* ================================================================

   ASSIGNMENT MODAL

================================================================ */

 

function assignCompany(id){

    const student = students.find(s => s.id === id);

    if(!student) return;

 

    const activeCompanies = companies.filter(c => c.status === "active");

    const companySelect = document.getElementById("assignmentCompany");

 

    document.getElementById("assignmentStudentDbId").value = student.id;

    document.getElementById("assignmentStudentAvatar").textContent = initials(student.name);

    document.getElementById("assignmentStudentName").textContent = student.name;

    document.getElementById("assignmentStudentId").textContent = "Student ID: " + student.studentId;

    document.getElementById("assignmentStudentProgram").textContent = student.program;

    

    companySelect.innerHTML = `<option value="">Select Active Company</option>` +

        activeCompanies.map(company =>

            `<option value="${escapeHtml(company.name)}" ${student.company === company.name ? 'selected' : ''}>${escapeHtml(company.name)}</option>`

        ).join("");

 

    document.getElementById("saveAssignmentBtn").disabled = activeCompanies.length === 0;

    document.getElementById("assignmentModal").classList.add("show");

}

 

/* ================================================================

   EVALUATIONS SYSTEM

================================================================ */

 

const evaluationCriteria = [

    ["attendance_punctuality", "Attendance & punctuality"],

    ["quality_of_work", "Quality of work"],

    ["productivity", "Productivity"],

    ["communication", "Communication"],

    ["teamwork", "Teamwork"],

    ["initiative", "Initiative"],

    ["professionalism", "Professionalism"],

    ["adaptability", "Adaptability"],

    ["problem_solving", "Problem solving"],

    ["technical_skills", "Technical skills"]

];

 

document.getElementById("evaluationCriteria").innerHTML = evaluationCriteria.map(([key, label]) => `

    <div class="evaluation-criterion">

        <span class="evaluation-criterion-name">${escapeHtml(label)}</span>

        <div class="evaluation-rating-options" role="radiogroup" aria-label="${escapeHtml(label)} rating">

            ${[1, 2, 3, 4, 5].map(rating => `

                <label class="evaluation-rating-option" for="evaluationScore-${key}-${rating}">

                    <input id="evaluationScore-${key}-${rating}" type="radio" name="scores[${key}]" value="${rating}" required>

                    <span>${rating}</span>

                </label>

            `).join("")}

        </div>

    </div>

`).join("");

 

function openEvaluationModal(studentId = "", draft = null){

    if(EVALUATION_DATA_ERROR){

        showToast("Evaluation records are unavailable. Check that intern_evaluations table exists.");

        return;

    }

    if(!students.length){

        showToast("Add a registered student before recording an evaluation.");

        return;

    }

 

    const form = document.querySelector("#evaluationModal form");

    const studentSelect = document.getElementById("evaluationStudent");

    form.reset();

    document.getElementById("evaluationRecordId").value = draft ? String(draft.id) : "";

    studentSelect.innerHTML = `<option value="">Select a student</option>` + students.map(student =>

        `<option value="${student.id}">${escapeHtml(student.name)} (${escapeHtml(student.studentId)})</option>`

    ).join("");

    studentSelect.value = studentId ? String(studentId) : "";

    

    if(draft){

        document.getElementById("evaluationDate").value = draft.evaluationDate;

        document.getElementById("evaluationPerformance").value = draft.overallPerformance;

        document.getElementById("evaluationStatus").value = draft.status;

        document.getElementById("evaluationStrengths").value = draft.strengths;

        document.getElementById("evaluationImprovements").value = draft.areasForImprovement;

        document.getElementById("evaluationComments").value = draft.comments;

        document.getElementById("evaluationRecommendation").value = draft.recommendation;

        evaluationCriteria.forEach(([key]) => {

            const camelCaseKey = key.replace(/_([a-z])/g, (_, letter) => letter.toUpperCase());

            const rating = document.querySelector(

                `input[name="scores[${key}]"][value="${draft[camelCaseKey]}"]`

            );

            if(rating) rating.checked = true;

        });

        document.querySelector("#evaluationModal .modal-head h2").textContent = "Continue draft evaluation";

    }

    document.getElementById("evaluationModal").classList.add("show");

}

 

function renderEvaluations(){

    const roster = document.getElementById("evaluationRoster");

    if(!roster) return;

 

    if(STUDENT_DATA_ERROR || !students.length){

        roster.innerHTML = `<div class="empty">No registered students found.</div>`;

        return;

    }

 

    roster.innerHTML = students.map(student => {

        const evaluation = evaluationsByStudentId[student.id];

        const hasEvaluation = Boolean(evaluation);

 

        return `

            <div class="eval-row">

                <div class="person">

                    <div class="mini-avatar">${initials(student.name)}</div>

                    <div>

                        <div class="person-name">${escapeHtml(student.name)}</div>

                        <div class="person-sub">${escapeHtml(student.studentId)}</div>

                    </div>

                </div>

 

                <div class="week">${escapeHtml(student.program)}</div>

                <div class="evaluation-placement evaluation-company">

                    <span>Company</span>

                    <strong>${escapeHtml(student.company || "Not assigned")}</strong>

                </div>

                <div class="evaluation-placement evaluation-hours">

                    <span>Working hours</span>

                    <strong>${Number(student.completedHours).toFixed(1)} hrs</strong>

                </div>

                <div class="week">${hasEvaluation ? `${Number(evaluation.overallRating).toFixed(2)} / 5` : "No evaluation"}</div>

                <span class="badge ${hasEvaluation && evaluation.status === "Completed" ? "green" : "yellow"}">${hasEvaluation ? escapeHtml(evaluation.status) : "Not evaluated"}</span>

                <div class="evaluation-actions">

                    <button class="small-btn evaluation-action-button" type="button" onclick="openEvaluationModal(${student.id})">${hasEvaluation ? "New Evaluation" : "Evaluate"}</button>

                </div>

            </div>

        `;

    }).join("");

}

 

document.getElementById("startEvaluationBtn")?.addEventListener("click", () => openEvaluationModal());

 

/* ================================================================

   MESSAGES SYSTEM

================================================================ */

 

const conversationList = document.getElementById("companyConversationList");

const conversationSearch = document.getElementById("conversationSearch");

const chatInput = document.getElementById("messageInput");

const sendMessageButton = document.getElementById("sendMessageBtn");
async function messageApi(action, companyId = null, options = {}){

    const params = new URLSearchParams();

    params.set("message_api", action);

    if(companyId !== null) params.set("company_id", String(companyId));

 

    const fetchOptions = {

        method: options.method || "GET",

        headers: options.headers || {}

    };

 

    if(options.body){

        fetchOptions.body = options.body;

    }

 

    const response = await fetch(MESSAGE_API_URL + "?" + params.toString(), fetchOptions);

    const text = await response.text();

    let data;

 

    try {

        data = JSON.parse(text);

    } catch(error) {

        throw new Error("Invalid server response.");

    }

 

    if(!response.ok || !data.success){

        throw new Error(data.message || "Message request failed.");

    }

 

    return data;

}

 

function renderCompanyConversations(){

    if(!conversationList) return;

 

    if(!companies.length){

        conversationList.innerHTML = `<div class="empty">No companies are registered yet.</div>`;

        return;

    }

 

    const searchTerm = (conversationSearch?.value || "").trim().toLowerCase();

    const filteredCompanies = companies.filter(company =>

        company.name.toLowerCase().includes(searchTerm) ||

        company.code.toLowerCase().includes(searchTerm)

    );

 

    conversationList.innerHTML = filteredCompanies.map(company => `

        <button class="conversation${currentConversation && String(currentConversation.companyId) === String(company.id) ? " active" : ""}"

            type="button"

            onclick="selectConversation(${company.id})">

            <span class="conversation-top">

                <span class="conversation-name">${escapeHtml(company.name)}</span>

            </span>

            <span class="conversation-msg">${escapeHtml(company.lastMessage || company.contact)}</span>

        </button>

    `).join("");

}

 

async function selectConversation(companyId){

    const selectedCompany = companies.find(c => Number(c.id) === companyId);

    if(!selectedCompany) return;

 

    currentConversation = { companyId: selectedCompany.id, name: selectedCompany.name };

    document.getElementById("chatName").textContent = selectedCompany.name;

    chatInput.disabled = false;

    sendMessageButton.disabled = false;

    

    try {

        const data = await messageApi("thread", companyId);

        const body = document.getElementById("chatBody");

        body.innerHTML = (data.messages || []).map(m => `

            <div class="bubble ${m.sender_type === 'employee' ? 'me' : ''}">

                ${escapeHtml(m.message)}

            </div>

        `).join("");

    } catch(err){

        console.error(err);

    }

}

 

/* ================================================================

   SUPPORT & PROFILE

================================================================ */

 

document.getElementById("supportBtn")?.addEventListener("click", () => {

    showToast("Your support request has been submitted.");

});

 

function showToast(message){

    const toast = document.getElementById("toast");

    toast.textContent = message;

    toast.classList.add("show");

    setTimeout(() => toast.classList.remove("show"), 2500);

}

 

function closeModal(id){

    document.getElementById(id).classList.remove("show");

}

 

document.querySelectorAll("[data-close]").forEach(button => {

    button.addEventListener("click", () => closeModal(button.dataset.close));

});

 

/* ================================================================

   INITIALIZATION

================================================================ */

 

renderCompaniesPage();

renderEvaluations();

renderCompanyConversations();

 

if(EVALUATION_SUCCESS_MESSAGE){

    showToast(EVALUATION_SUCCESS_MESSAGE);

}
