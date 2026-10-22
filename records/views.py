import json
from decimal import Decimal
from django.db.models import Avg
from django.http import JsonResponse, HttpResponse
from django.views.decorators.csrf import csrf
from .models import Student


def index(request):
    students = Student.objects.all().order_by("name")
    total_students = students.count()
    active_count = students.filter(status="Active").count()
    avg_gpa = students.aggregate(avg_gpa=Avg("gpa"))["avg_gpa"] or 0
    departments_count = students.values("department").distinct().count()

    html = f"""
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Record Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans antialiased">
    <div class="flex h-screen overflow-hidden">
        <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col hidden md:flex">
            <div class="p-5 text-xl font-bold text-white flex items-center gap-3 border-b border-slate-800">
                <i class="fa-solid fa-graduation-cap text-indigo-500"></i>
                <span>EduRecord</span>
            </div>
            <nav class="flex-1 p-4 space-y-2">
                <a href="#" class="flex items-center gap-3 px-4 py-3 bg-indigo-600 text-white rounded-lg font-medium">
                    <i class="fa-solid fa-chart-pie w-5"></i> Dashboard
                </a>
                <button type="button" onclick="openModal()" class="w-full flex items-center gap-3 px-4 py-3 hover:bg-slate-800 rounded-lg transition text-left">
                    <i class="fa-solid fa-user-plus w-5"></i> Add Student
                </button>
            </nav>
            <div class="p-4 border-t border-slate-800 text-sm text-slate-500">
                SRMS v1.0 &bull; Web Edition
            </div>
        </aside>

        <main class="flex-1 flex flex-col overflow-y-auto">
            <header class="bg-white border-b border-slate-200 h-16 flex items-center justify-between px-6">
                <div class="flex items-center gap-4">
                    <h1 class="text-xl font-bold text-slate-800">Student Management Dashboard</h1>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="exportJSON()" class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2 rounded-lg text-sm font-semibold transition flex items-center gap-2">
                        <i class="fa-solid fa-download"></i> Export Data
                    </button>
                    <button onclick="openModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm transition flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Student
                    </button>
                </div>
            </header>

            <div class="p-6 space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Students</p>
                        <h3 id="stat-total" class="text-2xl font-bold text-slate-800 mt-1">{total_students}</h3>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Active Status</p>
                        <h3 id="stat-active" class="text-2xl font-bold text-emerald-600 mt-1">{active_count}</h3>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Average GPA</p>
                        <h3 id="stat-gpa" class="text-2xl font-bold text-indigo-600 mt-1">{avg_gpa:.2f}</h3>
                    </div>
                    <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm">
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Departments</p>
                        <h3 id="stat-depts" class="text-2xl font-bold text-amber-600 mt-1">{departments_count}</h3>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" id="students">
                    <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="relative w-full sm:w-72">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <i class="fa-solid fa-search"></i>
                            </span>
                            <input type="text" id="searchInput" oninput="renderTable()" placeholder="Search by name, ID..." class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="flex items-center gap-2 w-full sm:w-auto">
                            <select id="filterDept" onchange="renderTable()" class="border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">All Departments</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Engineering">Engineering</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Arts & Sciences">Arts & Sciences</option>
                            </select>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold border-b border-slate-200">
                                    <th class="p-4">Student ID</th>
                                    <th class="p-4">Name</th>
                                    <th class="p-4">Department</th>
                                    <th class="p-4">Year</th>
                                    <th class="p-4">GPA</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="studentTableBody" class="divide-y divide-slate-200 text-sm text-slate-700"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <div id="studentModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-lg w-full overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 flex justify-between items-center">
                <h3 id="modalTitle" class="font-bold text-lg text-slate-800">Add New Student</h3>
                <button type="button" onclick="closeModal()" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>

            <form id="studentForm" class="p-6 space-y-4">
                <input type="hidden" id="editId">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Student ID</label>
                    <input type="text" id="studentId" required placeholder="e.g. CS-2026-001" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Full Name</label>
                    <input type="text" id="fullName" required placeholder="John Doe" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Department</label>
                        <select id="department" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="Computer Science">Computer Science</option>
                            <option value="Engineering">Engineering</option>
                            <option value="Business Administration">Business Administration</option>
                            <option value="Arts & Sciences">Arts & Sciences</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Year Level</label>
                        <select id="yearLevel" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">GPA (0.0 - 4.0)</label>
                        <input type="number" step="0.01" min="0" max="4" id="gpa" required placeholder="3.75" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase mb-1">Status</label>
                        <select id="status" required class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:outline-none">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                            <option value="Graduated">Graduated</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="button" onclick="closeModal()" class="px-4 py-2 border border-slate-300 text-slate-700 rounded-lg text-sm font-semibold hover:bg-slate-50 transition">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">Save Record</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let students = [];

        async function fetchStudents() {{
            const response = await fetch("/api/students/");
            const data = await response.json();
            students = data.students;
            renderTable();
            updateStats();
        }}

        function updateStats() {{
            document.getElementById("stat-total").innerText = students.length;
            const activeCount = students.filter(s => s.status === "Active").length;
            document.getElementById("stat-active").innerText = activeCount;

            const totalGpa = students.reduce((acc, curr) => acc + parseFloat(curr.gpa || 0), 0);
            const avgGpa = students.length ? (totalGpa / students.length).toFixed(2) : "0.00";
            document.getElementById("stat-gpa").innerText = avgGpa;

            const depts = new Set(students.map(s => s.department));
            document.getElementById("stat-depts").innerText = depts.size;
        }}

        function renderTable() {{
            const search = document.getElementById("searchInput").value.toLowerCase();
            const deptFilter = document.getElementById("filterDept").value;
            const tbody = document.getElementById("studentTableBody");
            tbody.innerHTML = "";

            const filtered = students.filter(s => {{
                const matchesSearch = s.name.toLowerCase().includes(search) || s.student_id.toLowerCase().includes(search);
                const matchesDept = deptFilter === "" || s.department === deptFilter;
                return matchesSearch && matchesDept;
            }});

            if (filtered.length === 0) {{
                tbody.innerHTML = `<tr><td colspan="7" class="p-6 text-center text-slate-400">No student records found.</td></tr>`;
                return;
            }}

            filtered.forEach((s) => {{
                let badgeColor = "bg-emerald-100 text-emerald-700";
                if (s.status === "Inactive") badgeColor = "bg-slate-100 text-slate-600";
                if (s.status === "Graduated") badgeColor = "bg-indigo-100 text-indigo-700";

                tbody.innerHTML += `
                    <tr class="hover:bg-slate-50 transition">
                        <td class="p-4 font-medium text-slate-800">${{s.student_id}}</td>
                        <td class="p-4 font-semibold text-slate-800">${{s.name}}</td>
                        <td class="p-4 text-slate-600">${{s.department}}</td>
                        <td class="p-4 text-slate-600">${{s.year}}</td>
                        <td class="p-4 font-semibold text-indigo-600">${{s.gpa}}</td>
                        <td class="p-4"><span class="px-2.5 py-1 rounded-full text-xs font-medium ${{badgeColor}}">${{s.status}}</span></td>
                        <td class="p-4 text-right space-x-2">
                            <button type="button" onclick="editStudent(${{s.id}})" class="text-slate-400 hover:text-indigo-600 transition"><i class="fa-solid fa-pen-to-square"></i></button>
                            <button type="button" onclick="deleteStudent(${{s.id}})" class="text-slate-400 hover:text-rose-600 transition"><i class="fa-solid fa-trash"></i></button>
                        </td>
                    </tr>
                `;
            }});
        }}

        function openModal() {{
            document.getElementById("studentForm").reset();
            document.getElementById("editId").value = "";
            document.getElementById("modalTitle").innerText = "Add New Student";
            document.getElementById("studentModal").classList.remove("hidden");
        }}

        function closeModal() {{
            document.getElementById("studentModal").classList.add("hidden");
        }}

        function editStudent(id) {{
            const student = students.find(s => s.id === id);
            if (!student) return;

            document.getElementById("editId").value = student.id;
            document.getElementById("modalTitle").innerText = "Edit Student Record";
            document.getElementById("studentId").value = student.student_id;
            document.getElementById("fullName").value = student.name;
            document.getElementById("department").value = student.department;
            document.getElementById("yearLevel").value = student.year;
            document.getElementById("gpa").value = student.gpa;
            document.getElementById("status").value = student.status;
            document.getElementById("studentModal").classList.remove("hidden");
        }}

        document.getElementById("studentForm").addEventListener("submit", async function (event) {{
            event.preventDefault();

            const id = document.getElementById("editId").value;
            const payload = {{
                student_id: document.getElementById("studentId").value,
                name: document.getElementById("fullName").value,
                department: document.getElementById("department").value,
                year: document.getElementById("yearLevel").value,
                gpa: document.getElementById("gpa").value,
                status: document.getElementById("status").value,
            }};

            const method = id ? "PUT" : "POST";
            const url = id ? `/api/students/${{id}}/` : "/api/students/";

            const response = await fetch(url, {{
                method,
                headers: {{
                    "Content-Type": "application/json",
                    "X-CSRFToken": getCookie("csrftoken")
                }},
                body: JSON.stringify(payload)
            }});

            if (response.ok) {{
                closeModal();
                fetchStudents();
            }} else {{
                alert("Error saving student record.");
            }}
        }});

        async function deleteStudent(id) {{
            if (!confirm("Are you sure you want to delete this student record?")) return;

            const response = await fetch(`/api/students/${{id}}/`, {{
                method: "DELETE",
                headers: {{
                    "Content-Type": "application/json",
                    "X-CSRFToken": getCookie("csrftoken")
                }}
            }});

            if (response.ok) {{
                fetchStudents();
            }} else {{
                alert("Error deleting student record.");
            }}
        }}

        function getCookie(name) {{
            let cookieValue = null;
            if (document.cookie && document.cookie !== "") {{
                const cookies = document.cookie.split(";");
                for (let i = 0; i < cookies.length; i++) {{
                    const cookie = cookies[i].trim();
                    if (cookie.substring(0, name.length + 1) === (name + "=")) {{
                        cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
                        break;
                    }}
                }}
            }}
            return cookieValue;
        }}

        function exportJSON() {{
            const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(students, null, 2));
            const downloadAnchor = document.createElement("a");
            downloadAnchor.setAttribute("href", dataStr);
            downloadAnchor.setAttribute("download", "student_records.json");
            document.body.appendChild(downloadAnchor);
            downloadAnchor.click();
            downloadAnchor.remove();
        }}

        fetchStudents();
    </script>
</body>
</html>
    """
    return HttpResponse(html)


@csrf
def student_api(request):
    if request.method == "GET":
        students = list(
            Student.objects.all().values("id", "student_id", "name", "department", "year", "gpa", "status").order_by("name")
        )
        return JsonResponse({"students": students}, safe=False)

    if request.method == "POST":
        data = json.loads(request.body)
        required = ["student_id", "name", "department", "year", "gpa", "status"]
        if not all(data.get(field) for field in required):
            return JsonResponse({"error": "Missing required fields"}, status=400)

        student = Student.objects.create(
            student_id=data["student_id"],
            name=data["name"],
            department=data["department"],
            year=data["year"],
            gpa=Decimal(str(data["gpa"])),
            status=data["status"],
        )

        return JsonResponse(
            {
                "id": student.id,
                "student_id": student.student_id,
                "name": student.name,
                "department": student.department,
                "year": student.year,
                "gpa": str(student.gpa),
                "status": student.status,
            },
            status=201,
        )

    return JsonResponse({"error": "Method not allowed"}, status=405)


@csrf
def student_detail_api(request, pk):
    try:
        student = Student.objects.get(pk=pk)
    except Student.DoesNotExist:
        return JsonResponse({"error": "Student not found"}, status=404)

    if request.method == "PUT":
        data = json.loads(request.body)
        student.student_id = data.get("student_id", student.student_id)
        student.name = data.get("name", student.name)
        student.department = data.get("department", student.department)
        student.year = data.get("year", student.year)
        student.gpa = Decimal(str(data.get("gpa", student.gpa)))
        student.status = data.get("status", student.status)
        student.save()
        return JsonResponse(
            {
                "id": student.id,
                "student_id": student.student_id,
                "name": student.name,
                "department": student.department,
                "year": student.year,
                "gpa": str(student.gpa),
                "status": student.status,
            }
        )

    if request.method == "DELETE":
        student.delete()
        return JsonResponse({"message": "Deleted successfully"}, status=200)

    return JsonResponse({"error": "Method not allowed"}, status=405)
