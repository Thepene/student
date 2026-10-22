from django.db import models


class Student(models.Model):
    student_id = models.CharField(max_length=50, unique=True)
    name = models.CharField(max_length=200)
    department = models.CharField(max_length=100)
    year = models.CharField(max_length=20)
    gpa = models.DecimalField(max_digits=3, decimal_places=2)
    status = models.CharField(max_length=20)

    def __str__(self):
        return f"{self.name} ({self.student_id})"
