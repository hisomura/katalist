<?php
namespace App\Controller;

use App\Controller\AppController;
use Cake\ORM\TableRegistry;

/**
 * Offers Controller
 *
 * @property \App\Model\Table\OffersTable $Offers
 *
 * @method \App\Model\Entity\Offer[]|\Cake\Datasource\ResultSetInterface paginate($object = null, array $settings = [])
 */
class OffersController extends AppController
{

    /**
     * Index method
     *
     * @return \Cake\Http\Response|void
     */
    public function index()
    {
        $this->paginate = [
            'contain' => ['ToUsers'],
            'conditions' => [
                'from_user_id' => $this->Auth->user('id'),
            ]
        ];
        $offers = $this->paginate($this->Offers);

        $statuses = $this->Offers->getDispStatuses();
        $this->set(compact('offers', 'statuses'));
    }

    public function offered()
    {
        $this->paginate = [
            'contain' => ['FromUsers'],
            'conditions' => [
                'to_user_id' => $this->Auth->user('id'),
            ]
        ];
        $offers = $this->paginate($this->Offers);

        $statuses = $this->Offers->getDispStatuses();
        $this->set(compact('offers', 'statuses'));
    }

    public function offeredView($id = null)
    {
        $offer = $this->Offers->find()
            ->where([
                'Offers.id' => $id,
                'Offers.to_user_id' => $this->Auth->user('id'),
            ])
            ->contain(['FromUsers', 'ToUsers'])
            ->first();

        if (empty($offer)) {
            return $this->redirect(['controller' => 'Offers', 'action' => 'index']);
        }

        $statuses = $this->Offers->getDispStatuses();
        $this->set(compact('offer', 'statuses'));
    }

    /**
     * View method
     *
     * @param string|null $id Offer id.
     * @return \Cake\Http\Response|void
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null)
    {
        $offer = $this->Offers->get($id, [
            'contain' => [],
        ]);

        $this->set('offer', $offer);
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $offer = $this->Offers->newEntity();
        if ($this->request->is('post')) {
            $offer = $this->Offers->patchEntity($offer, $this->request->getData());

            $this->Users = TableRegistry::getTableLocator()->get('Users');
            $to_user = $this->Users->find()
                ->where(['id' => $offer['to_user_id']])
                ->select(['price'])
                ->first();

            if (empty($to_user)) {
                $this->Flash->error(__('The offer could not be saved. Please, try again.'));
                return $this->redirect(['controller' => 'Users', 'action' => 'view', $offer['to_user_id']]);;
            }

            $offer['from_user_id'] = $this->Auth->user('id');
            $offer['price'] = $to_user->price;
            $offer['status'] = 1; // offered
            if ($this->Offers->save($offer)) {
                $this->Flash->success(__('The offer has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The offer could not be saved. Please, try again.'));
        }
        $this->set(compact('offer'));
    }

    /**
     * Edit method
     *
     * @param string|null $id Offer id.
     * @return \Cake\Http\Response|null Redirects on successful edit, renders view otherwise.
     * @throws \Cake\Network\Exception\NotFoundException When record not found.
     */
    public function edit($id = null)
    {
        $offer = $this->Offers->get($id, [
            'contain' => []
        ]);
        if ($this->request->is(['patch', 'post', 'put'])) {
            $offer = $this->Offers->patchEntity($offer, $this->request->getData());
            if ($this->Offers->save($offer)) {
                $this->Flash->success(__('The offer has been saved.'));

                return $this->redirect(['action' => 'index']);
            }
            $this->Flash->error(__('The offer could not be saved. Please, try again.'));
        }
        $this->set(compact('offer'));
    }

    /**
     * Delete method
     *
     * @param string|null $id Offer id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);
        $offer = $this->Offers->get($id);
        if ($this->Offers->delete($offer)) {
            $this->Flash->success(__('The offer has been deleted.'));
        } else {
            $this->Flash->error(__('The offer could not be deleted. Please, try again.'));
        }

        return $this->redirect(['action' => 'index']);
    }

    public function accept($id)
    {
        if (!$this->Offers->accept($id, $this->Auth->user('id'))) {
            $this->Flash->error('処理に失敗しました');
            return $this->redirect(['action' => 'offered']);
        }
        $this->Flash->success('オファーを承認しました');
        return $this->redirect(['action' => 'offered_view', $id]);
    }

    public function cancel($id)
    {
        if (!$this->Offers->cancel($id, $this->Auth->user('id'))) {
            $this->Flash->error('処理に失敗しました');
            return $this->redirect(['action' => 'offered']);
        }
        $this->Flash->success('オファーを拒否しました');
        return $this->redirect(['action' => 'offered_view', $id]);
    }
}
