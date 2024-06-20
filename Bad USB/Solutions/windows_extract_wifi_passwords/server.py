from flask import Flask, request, jsonify
import os

app = Flask(__name__)

# Endpoint to receive ZIP file via POST request
@app.route('/upload_zip', methods=['POST'])
def upload_zip():
    # Check if the POST request has the file part
    if 'file' not in request.files:
        return jsonify({'error': 'No file part in the request'}), 400
    
    file = request.files['file']
    
    # If user does not select file, browser also submit an empty part without filename
    if file.filename == '':
        return jsonify({'error': 'No selected file'}), 400
    
    # Check if the file is a ZIP file
    if file and file.filename.endswith('.zip'):
        # Save the file to the 'uploads' folder
        filename = secure_filename(file.filename)  # Ensures safe filename to prevent directory traversal attacks
        file.save(os.path.join('uploads', filename))
        return jsonify({'message': 'ZIP file uploaded successfully'}), 200
    else:
        return jsonify({'error': 'File must be in ZIP format'}), 400

if __name__ == '__main__':
    # Create the 'uploads' folder if it doesn't exist
    if not os.path.exists('uploads'):
        os.makedirs('uploads')
    
    # Run the server on 0.0.0.0:5000 (accessible from any external IP)
    app.run(host='0.0.0.0', port=5000, debug=True)
