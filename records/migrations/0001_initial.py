from django.db import migrations, models


class Migration(migrations.Migration):

    initial = True

    dependencies = []

    operations = [
        migrations.CreateModel(
            name="Student",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("student_id", models.CharField(max_length=50, unique=True)),
                ("name", models.CharField(max_length=200)),
                ("department", models.CharField(max_length=100)),
                ("year", models.CharField(max_length=20)),
                ("gpa", models.DecimalField(decimal_places=2, max_digits=3)),
                ("status", models.CharField(max_length=20)),
            ],
        ),
    ]
