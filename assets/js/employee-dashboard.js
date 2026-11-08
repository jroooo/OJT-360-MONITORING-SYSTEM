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

// Sign-out confirmation modal
const signOutBtn = document.getElementById("signOutBtn");
const signOutModal = document.getElementById("signoutConfirmModal");
const staySignedInBtn = document.getElementById("staySignedInBtn");
const confirmSignOutBtn = document.getElementById("confirmSignOutBtn");

signOutBtn?.addEventListener("click", (event) => {
    event.preventDefault();

    if (!signOutModal) {
        if (confirm("Are you sure you want to log out?")) {
            window.location.href = "logout.php";
        }
        return;
    }

    signOutModal.hidden = false;
    staySignedInBtn?.focus();
});

staySignedInBtn?.addEventListener("click", () => {
    signOutModal.hidden = true;
    signOutBtn?.focus();
});

confirmSignOutBtn?.addEventListener("click", () => {
    window.location.href = "logout.php";
});

signOutModal?.addEventListener("click", (event) => {
    if (event.target === signOutModal) {
        signOutModal.hidden = true;
        signOutBtn?.focus();
    }
});

document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && signOutModal && !signOutModal.hidden) {
        signOutModal.hidden = true;
        signOutBtn?.focus();
    }
});

// Keep the Adviser sidebar preference consistent across its separate PHP pages.
let sidebarPreferenceOpen = true;
try {
    const storedSidebarPreference = localStorage.getItem("ojt360-sidebar-open");
    sidebarPreferenceOpen = storedSidebarPreference === null || storedSidebarPreference === "true";
} catch (error) {
    console.error("Unable to load the sidebar preference.", error);
}
sidebar?.classList.toggle("compact", !sidebarPreferenceOpen);
if (settingsSidebarToggle) {
    settingsSidebarToggle.checked = sidebarPreferenceOpen;
    settingsSidebarToggle.addEventListener("change", () => {
        sidebarPreferenceOpen = settingsSidebarToggle.checked;
        sidebar?.classList.toggle("compact", !sidebarPreferenceOpen);
        try {
            localStorage.setItem("ojt360-sidebar-open", String(sidebarPreferenceOpen));
        } catch (error) {
            console.error("Unable to save the sidebar preference.", error);
        }
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

 

    topbarPageName.dataset.currentPage = pageLabels[pageName] || "Adviser";
    document.title = (pageLabels[pageName] || "Adviser") + " | OJT 360";

    closeSidebar();

    window.scrollTo({top:0,behavior:"smooth"});

}

 

const requestedSection = window.OJT360_INITIAL_SECTION || new URLSearchParams(window.location.search).get("section");

if(["overview", "interns", "evaluations", "messages", "companies", "support", "settings", "profile"].includes(requestedSection)){

    showPage(requestedSection);

}

 

navLinks.forEach(link => {

    link.addEventListener("click", event => {
        if (link.tagName.toLowerCase() !== "a") {
            showPage(link.dataset.page);
            return;
        }

        const pageName = link.dataset.page;
        if (!pageName || !document.getElementById("page-" + pageName)) return;

        event.preventDefault();
        if (window.location.pathname !== new URL(link.href, window.location.href).pathname) {
            window.history.pushState({ adviserSection: pageName }, "", link.href);
        }
        showPage(pageName);
    });

});

window.addEventListener("popstate", () => {
    const sectionByPath = {
        "adviser_overview.php": "overview",
        "adviser_interns.php": "interns",
        "adviser_evaluations.php": "evaluations",
        "adviser_messages.php": "messages",
        "adviser_companies.php": "companies",
        "employee_support.php": "support",
        "employee_settings.php": "settings",
        "employee_profile.php": "profile"
    };
    const section = sectionByPath[window.location.pathname.split("/").pop()] || "overview";
    showPage(section);
});

 

menuToggle.addEventListener("click", () => {

    if(window.innerWidth > 1000){

        sidebarPreferenceOpen = sidebar.classList.contains("compact");
        sidebar.classList.toggle("compact", !sidebarPreferenceOpen);
        if (settingsSidebarToggle) settingsSidebarToggle.checked = sidebarPreferenceOpen;
        try {
            localStorage.setItem("ojt360-sidebar-open", String(sidebarPreferenceOpen));
        } catch (error) {
            console.error("Unable to save the sidebar preference.", error);
        }

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
    const summary = document.getElementById("companySummary");
    const assignButton = document.getElementById("companyGlobalAssignButton");
    const search = (document.getElementById("companyPageSearch")?.value || "").toLowerCase().trim();

    assignButton.disabled = students.length === 0 || placementCompanies.length === 0;

 

    if(PLACEMENT_COMPANY_DATA_ERROR){
        container.innerHTML = `<div class="interns-flash error" role="alert">${escapeHtml(PLACEMENT_COMPANY_DATA_ERROR)}</div>`;
        return;
    }

    if(STUDENT_PROGRESS_DATA_ERROR){
        container.innerHTML = `<div class="interns-flash error" role="alert">${escapeHtml(STUDENT_PROGRESS_DATA_ERROR)}</div>`;
        return;
    }

    const assignedStudents = students.filter(student => Boolean(student.company));
    const unassignedInterns = students.filter(student => !student.company).length;
    const totalJournals = assignedStudents.reduce((total, student) => total + (student.journals || []).length, 0);
    const completedJournals = assignedStudents.reduce((total, student) =>
        total + (student.journals || []).filter(journal =>
            ["submitted", "reviewed"].includes(String(journal.status || "").toLowerCase())
        ).length, 0
    );
    const journalCompletion = totalJournals > 0 ? Math.round((completedJournals / totalJournals) * 100) : 0;

    summary.innerHTML = `
        <article class="company-summary-card">
            <span>Partner Companies</span>
            <strong>${placementCompanies.length}</strong>
        </article>
        <article class="company-summary-card">
            <span>Assigned Interns</span>
            <strong>${assignedStudents.length}</strong>
        </article>
        <article class="company-summary-card">
            <span>Unassigned Interns</span>
            <strong>${unassignedInterns}</strong>
        </article>
        <article class="company-summary-card">
            <span>Journal Completion</span>
            <strong>${journalCompletion}%</strong>
            <small>${completedJournals} of ${totalJournals} journals submitted or reviewed</small>
        </article>
    `;

    if(!placementCompanies.length){
        container.innerHTML = `<div class="empty company-no-companies">No partner companies registered yet. Add a company to start assigning interns.</div>`;
        return;
    }

    let html = "";

    placementCompanies.forEach(company => {
        const companyStudents = students.filter(s =>
            String(s.company || "").trim().toLowerCase() === String(company.name || "").trim().toLowerCase()
        );
        const companyHours = companyStudents.reduce((total, student) => total + Number(student.completedHours || 0), 0);
        const companyAbsences = companyStudents.reduce((total, student) => total + (student.absences || []).length, 0);
        const companyJournals = companyStudents.reduce((total, student) => total + (student.journals || []).length, 0);
        
        const matchesCompany = [
            company.name,
            company.representative,
            company.position,
            company.phone,
            company.email
        ].some(value => String(value || "").toLowerCase().includes(search));

        const matchesStudent = companyStudents.some(student =>
            [student.name, student.studentId, student.program, student.email]
                .some(value => String(value || "").toLowerCase().includes(search))
        );

        

        if(search && !matchesCompany && !matchesStudent) return;

 

        html += `

            <div class="section-card company-placement-card">

                <div class="section-head company-placement-head">

                    <div>

                        <div class="person">

                            <div class="mini-avatar company-placement-avatar">${initials(company.name)}</div>

                            <div>

                                <h3 class="company-placement-name">${escapeHtml(company.name)}</h3>

                                <p class="section-sub company-placement-meta">
                                    Representative: ${escapeHtml(company.representative)} (${escapeHtml(company.position)})
                                    | Contact: ${escapeHtml(company.phone)}
                                    | <a href="mailto:${escapeHtml(company.email)}">${escapeHtml(company.email)}</a>
                                </p>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="company-placement-summary" aria-label="${escapeHtml(company.name)} internship summary">
                    <span><strong>${companyStudents.length}</strong> ${companyStudents.length === 1 ? "Intern" : "Interns"}</span>
                    <span><strong>${companyHours.toFixed(1)}</strong> hrs</span>
                    <span><strong>${companyAbsences}</strong> ${companyAbsences === 1 ? "Absence" : "Absences"}</span>
                    <span><strong>${companyJournals}</strong> ${companyJournals === 1 ? "Journal" : "Journals"}</span>
                </div>

 

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>Intern Name</th>

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

                                    <td colspan="7"><div class="empty company-placement-empty">No interns assigned yet.</div></td>

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

                                    <td>
                                                        ${renderInternProgress(student)}
                                    </td>

                                    <td>

                                        <span class="badge ${student.absences.length > 0 ? 'yellow' : 'green'}">

                                            ${student.absences.length} record(s)

                                        </span>

                                    </td>

                                    <td>

                                        <span class="badge blue">

                                            ${student.journals.length} ${student.journals.length === 1 ? "journal" : "journals"}

                                        </span>

                                    </td>

                                    <td>

                                        <div class="action-row">

                                            <button class="small-btn primary company-view-record" type="button" onclick="openStudentAdviserRecord(${student.id})">View Record</button>

                                            <button class="small-btn company-reassign" type="button" onclick="assignCompany(${student.id})">Reassign</button>

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

 

    const allUnassignedStudents = students.filter(student => !student.company);
    const unassignedStudents = allUnassignedStudents.filter(student =>
        !search ||
        "unassigned".includes(search) ||
        [student.name, student.studentId, student.program, student.email]
            .some(value => String(value || "").toLowerCase().includes(search))
    );
    if(unassignedStudents.length > 0){

        html += `

            <div class="section-card company-unassigned-card">

                <div class="section-head">

                    <div>

                        <h3 class="company-unassigned-title">Unassigned Interns (${unassignedStudents.length})</h3>

                        <p class="section-sub">Interns currently waiting for company assignment.</p>

                    </div>

                </div>

                <div class="table-wrap">

                    <table>

                        <thead>

                            <tr>

                                <th>Intern Name</th>

                                <th>Student ID</th>

                                <th>Program</th>

                                <th>Institutional Email</th>

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

                                </tr>

                            `).join("")}

                        </tbody>

                    </table>

                </div>

            </div>

        `;

    }

 

    container.innerHTML = html || `<div class="empty">No matching companies or interns found.</div>`;

}

function renderInternProgress(student){
    const completedHours = Number(student.completedHours || 0);
    const requiredHours = Number(student.requiredHours || 0);
    const hasAbsences = (student.absences || []).length > 0;
    const isComplete = requiredHours > 0 && completedHours >= requiredHours;
    const status = isComplete ? "Completed" : hasAbsences ? "Needs Attention" : "In Progress";
    const statusClass = isComplete ? "green" : hasAbsences ? "yellow" : "blue";
    const hours = requiredHours > 0
        ? `${completedHours.toFixed(1)} / ${requiredHours.toFixed(1)} hrs`
        : `${completedHours.toFixed(1)} hrs`;

    return `<strong>${hours}</strong><br><span class="badge ${statusClass} company-progress-status">${status}</span>`;
}

function formatAttendanceDate(value){
    const match = String(value || "").match(/^(\d{4})-(\d{2})-(\d{2})/);
    if(!match) return "—";

    const date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
    return new Intl.DateTimeFormat("en-US", {
        month: "short",
        day: "2-digit",
        year: "numeric",
        timeZone: "UTC"
    }).format(date);
}

document.getElementById("companyPageSearch")?.addEventListener("input", renderCompaniesPage);

 

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

    const studentAttendance = student.attendance || [];
    const attendanceTbody = document.getElementById("advAttendanceTable");
    if(studentAttendance.length === 0){
        attendanceTbody.innerHTML = `<tr><td colspan="5"><div class="empty" style="padding:10px;">No attendance records logged.</div></td></tr>`;
    } else {
        attendanceTbody.innerHTML = studentAttendance.map(attendance => {
            const timeIn = String(attendance.actual_time_in || attendance.time_in || "");
            const timeOut = String(attendance.time_out || "");
            const loggedMinutes = attendance.logged_minutes === null ? null : Number(attendance.logged_minutes);

            return `
                <tr>
                    <td>${formatAttendanceDate(timeIn)}</td>
                    <td>${escapeHtml(attendance.company_name || "—")}</td>
                    <td>${escapeHtml(timeIn.slice(11, 16) || "—")}</td>
                    <td>${timeOut ? escapeHtml(timeOut.slice(11, 16) || "—") : "In progress"}</td>
                    <td>${loggedMinutes === null ? "—" : (loggedMinutes / 60).toFixed(2) + " hrs"}</td>
                </tr>
            `;
        }).join("");
    }

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

        jnlTbody.innerHTML = `<tr><td colspan="3"><div class="empty" style="padding:10px;">No weekly journals submitted yet.</div></td></tr>`;

    } else {

        jnlTbody.innerHTML = student.journals.map(journal => `
            <tr>
                <td>${escapeHtml(journal.week_start)}</td>
                <td>
                    <a class="journal-draft-toggle" href="adviser_journal.php?journal_id=${Number(journal.id)}">
                        ${escapeHtml(journal.title)}
                    </a>
                </td>
                <td><span class="badge ${journal.status === "reviewed" ? "green" : "yellow"}">${escapeHtml(journal.status || "draft")}</span></td>
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

    document.getElementById("assignmentStudentPicker").hidden = true;
    setAssignmentStudent(student.id);

    const registeredCompanies = placementCompanies;
    const companySelect = document.getElementById("assignmentCompany");

    companySelect.innerHTML = `<option value="">Select Company</option>` +

        registeredCompanies.map(company =>

            `<option value="${escapeHtml(company.name)}">${escapeHtml(company.name)}</option>`

        ).join("");

    companySelect.value = student.company || "";

    document.getElementById("saveAssignmentBtn").disabled = registeredCompanies.length === 0;

    document.getElementById("assignmentModal").classList.add("show");

}

function setAssignmentStudent(studentId){
    const student = students.find(record => record.id === Number(studentId));
    if(!student) return;

    document.getElementById("assignmentStudentDbId").value = student.id;
    document.getElementById("assignmentStudentAvatar").textContent = initials(student.name);
    document.getElementById("assignmentStudentName").textContent = student.name;
    document.getElementById("assignmentStudentId").textContent = "Student ID: " + student.studentId;
    document.getElementById("assignmentStudentProgram").textContent = student.program;
}

function openInternAssignment(){
    if(!students.length || !placementCompanies.length) return;
    const studentPicker = document.getElementById("assignmentStudentSelect");
    studentPicker.innerHTML = students.map(student =>
        `<option value="${student.id}">${escapeHtml(student.name)} (${escapeHtml(student.studentId)})</option>`
    ).join("");
    const firstUnassignedStudent = students.find(student => !student.company);
    studentPicker.value = String(firstUnassignedStudent ? firstUnassignedStudent.id : students[0].id);
    setAssignmentStudent(studentPicker.value);

    document.getElementById("assignmentStudentPicker").hidden = false;
    const companySelect = document.getElementById("assignmentCompany");
    companySelect.innerHTML = `<option value="">Select Company</option>` + placementCompanies.map(record =>
        `<option value="${escapeHtml(record.name)}">${escapeHtml(record.name)}</option>`
    ).join("");
    companySelect.value = students.find(student => student.id === Number(studentPicker.value))?.company || "";
    document.getElementById("saveAssignmentBtn").disabled = placementCompanies.length === 0;
    document.getElementById("assignmentModal").classList.add("show");
}

document.getElementById("assignmentStudentSelect").addEventListener("change", event => {
    setAssignmentStudent(event.target.value);
    const student = students.find(record => record.id === Number(event.target.value));
    document.getElementById("assignmentCompany").value = student?.company || "";
});

 

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
const messageLayout = document.querySelector("#page-messages .message-layout");
const conversationBackButton = document.getElementById("conversationBackBtn");

const chatInput = document.getElementById("messageInput");

const sendMessageButton = document.getElementById("sendMessageBtn");

let activeMessageIds = new Set();
let conversationLoadSequence = 0;
let conversationRefreshInProgress = false;
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

    if(PLACEMENT_COMPANY_DATA_ERROR){
        conversationList.innerHTML = `<div class="interns-flash error" role="alert">${escapeHtml(PLACEMENT_COMPANY_DATA_ERROR)}</div>`;
        return;
    }

 

    if(!companies.length){

        conversationList.innerHTML = `<div class="empty">No companies are registered yet.</div>`;

        return;

    }

 

    const searchTerm = (conversationSearch?.value || "").trim().toLowerCase();

    const filteredCompanies = companies.filter(company =>
        [
            company.name,
            company.representative,
            company.position,
            company.phone,
            company.email
        ].some(value => String(value || "").toLowerCase().includes(searchTerm))
    );

 

    conversationList.innerHTML = filteredCompanies.map(company => `

        <button class="conversation${currentConversation && String(currentConversation.companyId) === String(company.id) ? " active" : ""}"

            type="button"

            onclick="selectConversation(${company.id})">

            <span class="conversation-top">

                <span class="conversation-name">${escapeHtml(company.name)}</span>

            </span>

            <span class="conversation-msg">${escapeHtml(company.lastMessage || company.representative || company.email)}</span>

        </button>

    `).join("");

}

 

async function selectConversation(companyId){

    const selectedCompany = companies.find(c => Number(c.id) === Number(companyId));

    if(!selectedCompany) return;

    messageLayout?.classList.add("mobile-chat-open");
    const loadSequence = ++conversationLoadSequence;
    currentConversation = { companyId: selectedCompany.id, name: selectedCompany.name };
    activeMessageIds = new Set();
    document.getElementById("chatName").textContent = selectedCompany.name;

    chatInput.disabled = false;
    sendMessageButton.disabled = false;
    document.getElementById("chatStatus").textContent = selectedCompany.representative || selectedCompany.email;
    
    try {

        const data = await messageApi("thread", companyId);
        if(loadSequence !== conversationLoadSequence) return;

        const body = document.getElementById("chatBody");

        const messages = data.messages || [];
        activeMessageIds = new Set(messages.map(message => String(message.id)));
        body.innerHTML = messages.length ? messages.map(m => `

            <div class="bubble ${m.sender_type === 'employee' ? 'me' : ''}">

                ${escapeHtml(m.message)}

            </div>

        `).join("") : `<div class="empty chat-empty">No messages yet. Start the conversation below.</div>`;
        body.scrollTop = body.scrollHeight;

    } catch(err){

        if(loadSequence !== conversationLoadSequence) return;
        console.error(err);
        document.getElementById("chatBody").innerHTML = `<div class="interns-flash error" role="alert">${escapeHtml(err.message || "Unable to load this conversation.")}</div>`;

    }

}

conversationBackButton?.addEventListener("click", () => {
    messageLayout?.classList.remove("mobile-chat-open");
});

async function refreshActiveConversation(){
    const messagesPage = document.getElementById("page-messages");
    if(
        !currentConversation ||
        !messagesPage?.classList.contains("active") ||
        document.hidden ||
        conversationRefreshInProgress
    ){
        return;
    }

    conversationRefreshInProgress = true;
    const companyId = currentConversation.companyId;

    try {
        const data = await messageApi("thread", companyId);
        if(!currentConversation || Number(currentConversation.companyId) !== Number(companyId)) return;

        const messages = data.messages || [];
        const newCompanyMessages = messages.filter(message =>
            message.sender_type === "company" && !activeMessageIds.has(String(message.id))
        );
        const hasNewMessages = messages.some(message => !activeMessageIds.has(String(message.id)));
        if(!hasNewMessages) return;

        const body = document.getElementById("chatBody");
        const shouldScrollToBottom = body.scrollHeight - body.scrollTop - body.clientHeight < 80;
        body.innerHTML = messages.map(message => `
            <div class="bubble ${message.sender_type === "employee" ? "me" : ""}">
                ${escapeHtml(message.message)}
            </div>
        `).join("");
        if(shouldScrollToBottom) body.scrollTop = body.scrollHeight;
        activeMessageIds = new Set(messages.map(message => String(message.id)));

        const company = companies.find(item => Number(item.id) === Number(companyId));
        newCompanyMessages.forEach(message => {
            if(company){
                company.lastMessage = message.message;
                renderCompanyConversations();
            }
            window.dispatchEvent(new CustomEvent("ojt360:company-reply", {
                detail: {
                    id: "mysql-" + message.id,
                    companyId,
                    companyName: currentConversation?.name || "Company",
                    message: message.message
                }
            }));
        });
    } catch(error) {
        console.error("Unable to refresh the active company conversation:", error);
    } finally {
        conversationRefreshInProgress = false;
    }
}

window.setInterval(refreshActiveConversation, 4000);

async function sendCurrentMessage(){
    if(!currentConversation || !chatInput || !sendMessageButton) return;

    const message = chatInput.value.trim();
    if(!message) return;

    const formData = new FormData();
    formData.set("message", message);
    sendMessageButton.disabled = true;

    try {
        const data = await messageApi("send", currentConversation.companyId, {
            method: "POST",
            body: formData
        });

        const body = document.getElementById("chatBody");
        body.querySelector("[data-empty-chat]")?.remove();
        body.querySelector(".chat-empty")?.remove();

        const messageBubble = document.createElement("div");
        messageBubble.className = "bubble me";
        messageBubble.textContent = data.message.message;
        body.append(messageBubble);
        activeMessageIds.add(String(data.message.id));
        body.scrollTop = body.scrollHeight;

        chatInput.value = "";
        const sentCompany = companies.find(company => Number(company.id) === Number(currentConversation.companyId));
        if(sentCompany){
            sentCompany.lastMessage = message;
            renderCompanyConversations();
        }
    } catch(error) {
        console.error(error);
        showToast(error.message || "Unable to send the message. Please try again.");
    } finally {
        sendMessageButton.disabled = !currentConversation;
    }
}

sendMessageButton?.addEventListener("click", sendCurrentMessage);
chatInput?.addEventListener("keydown", event => {
    if(event.key === "Enter" && !event.shiftKey){
        event.preventDefault();
        sendCurrentMessage();
    }
});

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

const requestedCompanyId = Number(new URLSearchParams(window.location.search).get("company_id"));
if(requestedSection === "messages" && requestedCompanyId > 0){
    selectConversation(requestedCompanyId);
}

 

if(EVALUATION_SUCCESS_MESSAGE){

    showToast(EVALUATION_SUCCESS_MESSAGE);

}
