<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=<, initial-scale=1.0">
    <title>Document</title>
</head>
<style>
    div.search-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
        border: 1px solid #ccc;
        border-radius: 5px;
        background-color: #de9f9f;
        align-items: center;
    }


    body {
        font-family: Arial, sans-serif;
        margin: 20px;
        align-items: center;
    
    }

    label {
        font-weight: bold;
    }

    input[type="text"] {
        padding: 5px;
        margin-right: 10px;
        width: 200px;
    }

    button {
        padding: 5px 10px;
        background-color: #007BFF;
        color: white;
        border: none;
        cursor: pointer;
    }

    button:hover {
        background-color: #0056b3;
    }
    </style>
<body>
    
        <h1>Search Page</h1>
        <form method="get" action="search.php">
             <div class="search-container">
    <label for="search">Search:</label>
    <input
        type="text"
        id="search"
        name="search"
        placeholder="Search..."
    >
    <button type="submit">Search</button>
</form>
    </div>
</body>
</html>