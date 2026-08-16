<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Report Issue | CampusFix</title>

    <link rel="stylesheet" href="../../assets/css/app.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/forms.css">
    <link rel="stylesheet" href="../../assets/css/responsive.css">
</head>

<body>

<div class="dashboard">

    <aside class="sidebar">
        <h2>CampusFix</h2>

        <a href="../dashboard.php">Dashboard</a>
        <a href="report.php" class="active">Report Issue</a>
        <a href="my_issues.php">My Issues</a>
        <a href="../lostfound/index.php">Lost & Found</a>
        <a href="../lostfound/create.php">Add Lost/Found Item</a>
        <a href="../../logout.php">Logout</a>
    </aside>

    <main class="main-content">

        <div class="page-header">
            <div>
                <h1>Report Campus Issue</h1>
                <p>
                    Submit a campus problem so it can be reviewed
                    and assigned to the appropriate staff member.
                </p>
            </div>
        </div>

        <section class="panel">

            <form
                action="duplicate_check.php"
                method="POST"
                enctype="multipart/form-data"
            >

                <div class="form-group">
                    <label for="title">Issue Title</label>

                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control"
                        maxlength="200"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="description">Description</label>

                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        required
                    ></textarea>
                </div>

                <div class="form-group">
                    <label for="category_id">Category</label>

                    <select
                        id="category_id"
                        name="category_id"
                        class="form-control"
                        required
                    >
                        <option value="">
                            Select a category
                        </option>

                    </select>
                </div>

                <div class="form-group">
                    <label for="location_id">Location</label>

                    <select
                        id="location_id"
                        name="location_id"
                        class="form-control"
                        required
                    >
                        <option value="">
                            Select a location
                        </option>

                    </select>
                </div>

                <div class="form-group">
                    <label for="priority">Priority</label>

                    <select
                        id="priority"
                        name="priority"
                        class="form-control"
                        required
                    >
                        <option value="Low">Low</option>
                        <option value="Medium" selected>
                            Medium
                        </option>
                        <option value="High">High</option>
                        <option value="Emergency">
                            Emergency
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="visibility">Visibility</label>

                    <select
                        id="visibility"
                        name="visibility"
                        class="form-control"
                        required
                    >
                        <option value="Public" selected>
                            Public
                        </option>
                        <option value="Private">
                            Private
                        </option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="image">Supporting Image</label>

                    <input
                        type="file"
                        id="image"
                        name="image"
                        class="form-control"
                        accept="image/jpeg,image/png"
                    >
                </div>

                <div class="form-group">
                    <button
                        type="submit"
                        class="btn btn-primary"
                    >
                        Check & Submit Issue
                    </button>
                </div>

            </form>

        </section>

    </main>

</div>

</body>
</html>
