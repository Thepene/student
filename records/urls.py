from django.urls import path
from . import views

urlpatterns = [
    path("", views.index, name="index"),
    path("api/students/", views.student_api, name="student_api"),
    path("api/students/<int:pk>/", views.student_detail_api, name="student_detail_api"),
]
