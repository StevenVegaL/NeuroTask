FROM node:16

WORKDIR /home/node/app

COPY package*.json ./
RUN npm install

COPY ./src ./src

EXPOSE 3011

CMD ["node", "./src/index.js"]