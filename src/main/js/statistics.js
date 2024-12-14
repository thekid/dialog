class Statistics {
  #tasks = {};

  /** Adds a statistics target with a given delay */
  add(target, signature, delay) {
    this.#tasks[target] = { signature, delay, timer : null, completed : false };
    return target;
  }

  /** Schedules the given statistics target */
  schedule(target) {
    const task = this.#tasks[target];
    if (task === undefined) throw new Error(`Undefined target ${target}`);

    task.timer && clearTimeout(task.timer);
    task.completed || (task.timer = setTimeout(
      () => fetch('/api/statistics/' + target, { method: 'POST', body: task.signature }).then(() => task.completed = true),
      task.delay
    ));
  }

  /** Withdraws any scheduling for the given statistics target */
  withdraw(target) {
    const task = this.#tasks[target];
    if (task === undefined) return;

    clearTimeout(task.timer);
    task.timer = null; 
  }
}