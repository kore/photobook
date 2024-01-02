import cv2
import sys
import json
import os
import dlib

# Constants for exit codes
EXIT_CODE_MISSING_ARG = 64

def main(imageFile):
    # Load the image
    image = cv2.imread(imageFile)

    # Determine a minumum size for faces we keep after detection
    minimumFaceArea = 0.005 * image.shape[0] * image.shape[1]

    # Load dlib face detector
    detector = dlib.get_frontal_face_detector();
    image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
    # @TODO: It would help the performance to scale down the image
    rectangles = detector(image, 1)
    faces = [convert_and_trim_bb(image, r) for r in rectangles]

    # Sort faces based on the size (largest to smallest)
    faces = sorted(faces, key=lambda face: face[2] * face[3], reverse=True)

    # Draw rectangles around each face
    for (x, y, w, h) in faces:
        cv2.rectangle(image, (x, y), (x+w, y+h), (255, 0, 0), 2)

    # Save the output image with detected faces
    cv2.imwrite(os.path.basename(imageFile) + '.faces.jpg', image)

    # Convert faces to JSON array of objects
    faces_json = json.dumps([{"x": int(x), "y": int(y), "width": int(w), "height": int(h)} for (x, y, w, h) in faces])

    # Print the faces
    print(faces_json)

def convert_and_trim_bb(image, rect):
    # extract the starting and ending (x, y)-coordinates of the
    # bounding box
    startX = rect.left()
    startY = rect.top()
    endX = rect.right()
    endY = rect.bottom()
    # ensure the bounding box coordinates fall within the spatial
    # dimensions of the image
    startX = max(0, startX)
    startY = max(0, startY)
    endX = min(endX, image.shape[1])
    endY = min(endY, image.shape[0])
    # compute the width and height of the bounding box
    w = endX - startX
    h = endY - startY
    # return our bounding box coordinates
    return (startX, startY, w, h)

if __name__ == "__main__":
    # Check if the image path is provided as a command-line argument
    if len(sys.argv) != 2:
        print("Error: No image path provided. Please provide the path to an image.")
        sys.exit(EXIT_CODE_MISSING_ARG)

    # Run the main function with the provided image path
    main(sys.argv[1])

